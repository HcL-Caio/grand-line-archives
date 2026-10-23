-- Extensão editorial do Grand Line Archives. Executar após database/schema.sql.
-- Idempotente: as tabelas não são recriadas ao repetir o script.
USE grand_line_archives;

CREATE TABLE IF NOT EXISTS usuarios (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  perfil ENUM('usuario','administrador') NOT NULL DEFAULT 'usuario',
  ativo BOOLEAN NOT NULL DEFAULT TRUE,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favoritos (
  usuario_id BIGINT UNSIGNED NOT NULL,
  carta_codigo VARCHAR(32) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (usuario_id, carta_codigo),
  CONSTRAINT fk_fav_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
  CONSTRAINT fk_fav_carta FOREIGN KEY (carta_codigo) REFERENCES cartas(codigo) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cores (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(32) NOT NULL UNIQUE
) ENGINE=InnoDB;
INSERT IGNORE INTO cores (nome) VALUES ('Red'),('Green'),('Blue'),('Purple'),('Black'),('Yellow');

CREATE TABLE IF NOT EXISTS carta_cores (
  carta_codigo VARCHAR(32) NOT NULL,
  cor_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (carta_codigo, cor_id),
  FOREIGN KEY (carta_codigo) REFERENCES cartas(codigo) ON DELETE CASCADE,
  FOREIGN KEY (cor_id) REFERENCES cores(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipos_impressos (
  id SMALLINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(90) NOT NULL UNIQUE
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS carta_tipos_impressos (
  carta_codigo VARCHAR(32) NOT NULL,
  tipo_id SMALLINT UNSIGNED NOT NULL,
  PRIMARY KEY (carta_codigo, tipo_id),
  FOREIGN KEY (carta_codigo) REFERENCES cartas(codigo) ON DELETE CASCADE,
  FOREIGN KEY (tipo_id) REFERENCES tipos_impressos(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ilustracoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  categoria_origem VARCHAR(70) DEFAULT NULL,
  ilustrador VARCHAR(160) DEFAULT NULL,
  observacoes TEXT DEFAULT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Uma impressão usa uma ilustração, mas a mesma ilustração pode ser reutilizada.
CREATE TABLE IF NOT EXISTS impressao_ilustracao (
  impressao_id BIGINT UNSIGNED PRIMARY KEY,
  ilustracao_id BIGINT UNSIGNED NOT NULL,
  FOREIGN KEY (impressao_id) REFERENCES impressoes_cartas(id) ON DELETE CASCADE,
  FOREIGN KEY (ilustracao_id) REFERENCES ilustracoes(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS fontes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  titulo VARCHAR(255) NOT NULL,
  autoria VARCHAR(160) DEFAULT NULL,
  url VARCHAR(1024) DEFAULT NULL,
  consultada_em DATE DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS referencias_narrativas (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  ilustracao_id BIGINT UNSIGNED NOT NULL,
  contexto TEXT NOT NULL,
  arco VARCHAR(160) DEFAULT NULL,
  capitulo VARCHAR(40) DEFAULT NULL,
  episodio VARCHAR(40) DEFAULT NULL,
  localizacao VARCHAR(255) DEFAULT NULL,
  verificacao ENUM('em_pesquisa','provavel','confirmado','sem_correspondencia') NOT NULL DEFAULT 'em_pesquisa',
  publicacao ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho',
  revisado_por BIGINT UNSIGNED DEFAULT NULL,
  revisado_em DATETIME DEFAULT NULL,
  FOREIGN KEY (ilustracao_id) REFERENCES ilustracoes(id) ON DELETE RESTRICT,
  FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS referencia_fontes (
  referencia_id BIGINT UNSIGNED NOT NULL,
  fonte_id BIGINT UNSIGNED NOT NULL,
  PRIMARY KEY (referencia_id, fonte_id),
  FOREIGN KEY (referencia_id) REFERENCES referencias_narrativas(id) ON DELETE CASCADE,
  FOREIGN KEY (fonte_id) REFERENCES fontes(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS produtos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(40) DEFAULT NULL,
  nome VARCHAR(255) NOT NULL,
  natureza ENUM('produto','evento') NOT NULL DEFAULT 'produto',
  regiao VARCHAR(80) DEFAULT NULL,
  data_distribuicao DATE DEFAULT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS distribuicoes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  impressao_id BIGINT UNSIGNED NOT NULL,
  produto_id BIGINT UNSIGNED NOT NULL,
  fonte_id BIGINT UNSIGNED DEFAULT NULL,
  modalidade VARCHAR(120) DEFAULT NULL,
  observacoes TEXT DEFAULT NULL,
  FOREIGN KEY (impressao_id) REFERENCES impressoes_cartas(id) ON DELETE RESTRICT,
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE RESTRICT,
  FOREIGN KEY (fonte_id) REFERENCES fontes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS explicacoes_cartas (
  carta_codigo VARCHAR(32) PRIMARY KEY,
  explicacao TEXT NOT NULL,
  publicacao ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho',
  revisado_por BIGINT UNSIGNED DEFAULT NULL,
  revisado_em DATETIME DEFAULT NULL,
  FOREIGN KEY (carta_codigo) REFERENCES cartas(codigo) ON DELETE CASCADE,
  FOREIGN KEY (revisado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS glossario_termos (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  termo VARCHAR(100) NOT NULL UNIQUE,
  traducao VARCHAR(120) DEFAULT NULL,
  definicao TEXT NOT NULL,
  fonte_url VARCHAR(1024) DEFAULT NULL,
  situacao ENUM('rascunho','publicado') NOT NULL DEFAULT 'rascunho'
) ENGINE=InnoDB;
