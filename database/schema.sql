-- Grand Line Archives | MySQL 8+ / MariaDB 10.5+
-- Cartas distintas e impressões/variantes são entidades diferentes.
CREATE DATABASE IF NOT EXISTS grand_line_archives
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE grand_line_archives;

CREATE TABLE IF NOT EXISTS cartas (
  codigo VARCHAR(32) NOT NULL,
  nome VARCHAR(255) NOT NULL,
  tipo VARCHAR(40) DEFAULT NULL,
  raridade VARCHAR(40) DEFAULT NULL,
  cores VARCHAR(120) DEFAULT NULL,
  atributo VARCHAR(100) DEFAULT NULL,
  custo SMALLINT UNSIGNED DEFAULT NULL,
  poder INT UNSIGNED DEFAULT NULL,
  contra_ataque INT DEFAULT NULL,
  vida SMALLINT UNSIGNED DEFAULT NULL,
  bloco SMALLINT UNSIGNED DEFAULT NULL,
  colecao_codigo VARCHAR(20) DEFAULT NULL,
  efeito MEDIUMTEXT DEFAULT NULL,
  gatilho MEDIUMTEXT DEFAULT NULL,
  url_fonte VARCHAR(512) NOT NULL,
  importado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (codigo),
  KEY idx_cartas_nome (nome),
  KEY idx_cartas_tipo (tipo),
  KEY idx_cartas_colecao (colecao_codigo),
  KEY idx_cartas_custo (custo),
  KEY idx_cartas_poder (poder)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS impressoes_cartas (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  carta_codigo VARCHAR(32) NOT NULL,
  idioma VARCHAR(8) NOT NULL DEFAULT 'en',
  chave_impressao CHAR(64) NOT NULL,
  colecao_codigo VARCHAR(20) DEFAULT NULL,
  arte VARCHAR(80) DEFAULT NULL,
  url_imagem VARCHAR(1024) NOT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_impressao (carta_codigo, idioma, chave_impressao),
  KEY idx_impressoes_colecao (colecao_codigo),
  CONSTRAINT fk_impressoes_carta FOREIGN KEY (carta_codigo)
    REFERENCES cartas (codigo) ON DELETE CASCADE
) ENGINE=InnoDB;
