#!/usr/bin/env python3
"""Coletor educacional de páginas públicas OPlayTCG em inglês.

Consulta robots.txt, respeita pausas e salva JSONL para retomar a coleta.
Não baixa nem redistribui arquivos de imagens.
"""
from __future__ import annotations

import argparse
import json
import re
import sys
import time
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urljoin, urlparse, urlunparse
from urllib.request import Request, urlopen
from urllib.robotparser import RobotFileParser

from bs4 import BeautifulSoup

BASE = "https://oplaytcg.com"
INDEX = "/en/library/all"
USER_AGENT = "GrandLineArchivesEducationalImporter/0.1 (https://github.com/HcL-Caio/grand-line-archives)"
CARD_PATH = re.compile(r"^/en/cards/([A-Za-z0-9-]{4,32})/?$")
KNOWN_TYPES = {"Leader", "Character", "Event", "Stage", "DON!!"}
KNOWN_RARITIES = {"C", "UC", "R", "SR", "SEC", "L", "P", "SP", "TR", "LEADER", "COMMON", "UNCOMMON", "RARE", "SUPER RARE", "SECRET RARE"}
SECTION_MARKS = {"Archetype", "Effect", "Trigger", "Printings", "Official Q&A", "Set"}


def get_html(path: str, delay: float) -> str:
    url = urljoin(BASE, path)
    for attempt in range(4):
        try:
            request = Request(url, headers={"User-Agent": USER_AGENT, "Accept": "text/html"})
            with urlopen(request, timeout=25) as response:
                if "text/html" not in response.headers.get("Content-Type", ""):
                    raise RuntimeError(f"Resposta inesperada, não HTML: {url}")
                payload = response.read(3_000_001)
                if len(payload) > 3_000_000:
                    raise RuntimeError(f"HTML grande demais: {url}")
                return payload.decode("utf-8", errors="replace")
        except HTTPError as exc:
            if exc.code not in (429, 500, 502, 503, 504) or attempt == 3:
                raise
            retry_header = exc.headers.get("Retry-After", "")
            pause = min(120.0, max(delay, float(retry_header))) if retry_header.isdigit() else 2 ** (attempt + 2)
        except (URLError, TimeoutError) as exc:
            if attempt == 3:
                raise RuntimeError(f"Falha ao acessar {url}: {exc}") from exc
            pause = 2 ** (attempt + 2)
        print(f"[aviso] Falha temporária em {url}. Nova tentativa após {pause}s", file=sys.stderr)
        time.sleep(pause)
    raise RuntimeError(f"Não foi possível acessar: {url}")


def check_robots() -> float:
    """Retorna crawl-delay. Sem robots.txt (404), usa a pausa conservadora."""
    url = urljoin(BASE, "/robots.txt")
    request = Request(url, headers={"User-Agent": USER_AGENT})
    try:
        with urlopen(request, timeout=15) as response:
            contents = response.read(200_000).decode("utf-8", "replace").splitlines()
    except HTTPError as exc:
        if exc.code == 404:
            print("[aviso] /robots.txt indisponível (404); use taxa conservadora.")
            return 0.0
        raise RuntimeError(f"Robots bloqueado (HTTP {exc.code}); coleta interrompida.") from exc
    except (URLError, TimeoutError) as exc:
        raise RuntimeError(f"Não foi possível conferir robots.txt; coleta interrompida: {exc}") from exc
    robot = RobotFileParser()
    robot.parse(contents)
    for path in (INDEX, "/en/cards/OP01-001"):
        if not robot.can_fetch(USER_AGENT, urljoin(BASE, path)):
            raise RuntimeError(f"robots.txt não autoriza a coleta de {path}.")
    return float(robot.crawl_delay(USER_AGENT) or 0)


def discover_cards(html: str) -> list[tuple[str, str]]:
    soup = BeautifulSoup(html, "html.parser")
    result: dict[str, str] = {}
    for anchor in soup.select("a[href]"):
        url = urljoin(BASE, anchor.get("href", ""))
        parsed = urlparse(url)
        if parsed.netloc != urlparse(BASE).netloc:
            continue
        match = CARD_PATH.match(parsed.path)
        if match:
            result[match.group(1)] = parsed.path
    return list(result.items())


def strings_of(soup: BeautifulSoup) -> list[str]:
    main = soup.find("main") or soup
    return [re.sub(r"\s+", " ", str(value)).strip() for value in main.stripped_strings if str(value).strip()]


def next_value(tokens: list[str], key: str, numeric: bool = True) -> str | None:
    for i, token in enumerate(tokens[:-1]):
        if token.casefold() == key.casefold():
            for value in tokens[i + 1:i + 4]:
                if numeric and re.fullmatch(r"[+-]?[\d,]+", value):
                    return value.replace(",", "")
                if not numeric and value not in SECTION_MARKS:
                    return value
    return None


def as_int(value: str | None) -> int | None:
    return int(value) if value is not None and re.fullmatch(r"\d+", value) else None


def section_text(tokens: list[str], start: str, end: set[str]) -> str | None:
    for index, value in enumerate(tokens):
        if value.casefold() == start.casefold():
            parts = []
            for chunk in tokens[index + 1:]:
                if chunk in end or any(chunk.startswith(x + " ·") for x in end):
                    break
                parts.append(chunk)
            value = " ".join(parts).strip()
            return value or None
    return None


def extract_images(soup: BeautifulSoup, codigo: str) -> list[dict]:
    found: dict[str, dict] = {}
    for img in soup.select("img"):
        candidates = [img.get("src"), img.get("data-src")]
        parent = img.find_parent("a", href=True)
        if parent:
            candidates.append(parent.get("href"))
        for srcset in (img.get("srcset"), img.get("data-srcset")):
            if srcset:
                candidates.extend(chunk.strip().split(" ")[0] for chunk in srcset.split(","))
        for candidate in candidates:
            if not candidate:
                continue
            url = urljoin(BASE, candidate)
            parsed = urlparse(url)
            if parsed.hostname != "cards.oplaytcg.com" or "/en/" not in parsed.path:
                continue
            if codigo.lower() not in parsed.path.lower() and "/tcg/" not in parsed.path:
                continue
            canonical_path = parsed.path.replace("/small/", "/")
            canonical = urlunparse((parsed.scheme, parsed.netloc, canonical_path, "", "", ""))
            components = [x for x in parsed.path.split("/") if x]
            collection = components[0] if components else None
            label = (img.get("alt") or "").strip()[:80] or None
            found[canonical] = {"url_imagem": canonical, "colecao_codigo": collection, "arte": label}
    return list(found.values())


def extract_card(html: str, codigo: str, path: str) -> dict:
    soup = BeautifulSoup(html, "html.parser")
    h1 = soup.find("h1")
    if not h1:
        raise ValueError(f"Página {codigo} sem título de carta.")
    name = h1.get_text(" ", strip=True)
    tokens = strings_of(soup)
    try:
        at = tokens.index(name)
        near_title = tokens[at + 1:at + 10]
    except ValueError:
        near_title = tokens[:15]
    card_type = next((s for s in near_title if s in KNOWN_TYPES), None)
    rarity = next((s for s in near_title if s.upper() in KNOWN_RARITIES), None)
    attribute = None
    if card_type:
        pos = near_title.index(card_type)
        if pos + 1 < len(near_title) and near_title[pos + 1] not in SECTION_MARKS and not near_title[pos + 1].isdigit():
            attribute = near_title[pos + 1]
    collection = codigo.split("-")[0] if "-" in codigo else None
    return {
        "codigo": codigo,
        "nome": name,
        "tipo": card_type,
        "raridade": rarity,
        "cores": None,  # Não inferir cor do CSS ou da arte.
        "atributo": attribute,
        "custo": as_int(next_value(tokens, "Cost")),
        "poder": as_int(next_value(tokens, "Power")),
        "contra_ataque": as_int(next_value(tokens, "Counter")),
        "vida": as_int(next_value(tokens, "Life")),
        "bloco": as_int(next_value(tokens, "Block")),
        "colecao_codigo": collection,
        "efeito": section_text(tokens, "Effect", {"Printings", "Official Q&A", "Trigger"}),
        "gatilho": section_text(tokens, "Trigger", {"Printings", "Official Q&A", "Effect"}),
        "url_fonte": urljoin(BASE, path),
        "impressoes": extract_images(soup, codigo),
    }


def collected(path: Path) -> set[str]:
    completed: set[str] = set()
    if path.is_file():
        for line in path.read_text(encoding="utf-8").splitlines():
            try:
                item = json.loads(line)
                if item.get("codigo") and item.get("nome"):
                    completed.add(item["codigo"])
            except json.JSONDecodeError:
                print("[aviso] Linha JSONL inválida ignorada.", file=sys.stderr)
    return completed


def main() -> None:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--limite", type=int, default=0, help="0 = todas; recomendado 5 para teste inicial")
    parser.add_argument("--pausa", type=float, default=1.5, help="Pausa entre páginas, mínimo 1 segundo")
    parser.add_argument("--saida", type=Path, default=Path("database/data/oplaytcg_en.jsonl"))
    args = parser.parse_args()
    if args.limite < 0:
        parser.error("--limite não pode ser negativo")
    delay = max(1.0, args.pausa, check_robots())
    index_html = get_html(INDEX, delay)
    discovered = discover_cards(index_html)
    if len(discovered) < 2000:
        raise RuntimeError(f"Índice inesperadamente pequeno ({len(discovered)} cartas). Layout mudou? Não prosseguindo.")
    print(f"Índice: {len(discovered)} códigos encontrados.")
    if args.limite:
        discovered = discovered[:args.limite]
    args.saida.parent.mkdir(parents=True, exist_ok=True)
    already = collected(args.saida)
    new_count = 0
    with args.saida.open("a", encoding="utf-8") as output:
        for codigo, path in discovered:
            if codigo in already:
                continue
            time.sleep(delay)
            try:
                card = extract_card(get_html(path, delay), codigo, path)
            except (HTTPError, URLError, RuntimeError, ValueError) as exc:
                print(f"[erro] {codigo}: {exc}", file=sys.stderr)
                continue
            output.write(json.dumps(card, ensure_ascii=False) + "\n")
            output.flush()
            new_count += 1
            if new_count % 25 == 0:
                print(f"Importáveis novos: {new_count}; último: {codigo}")
    print(f"Concluído. {new_count} registros adicionados em {args.saida}. Reexecutar retoma os pendentes.")


if __name__ == "__main__":
    main()
