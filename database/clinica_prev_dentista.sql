-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 03/02/2026 às 15:00
-- Versão do servidor: 8.4.7
-- Versão do PHP: 8.3.28

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `clinica_prev_dentista`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimentos`
--

DROP TABLE IF EXISTS `atendimentos`;
CREATE TABLE IF NOT EXISTS `atendimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `valor_total` decimal(10,2) DEFAULT '0.00',
  `data_atendimento` datetime NOT NULL,
  `paciente_nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `id_dentista` int NOT NULL,
  `taxa_cartao` decimal(10,2) DEFAULT '0.00',
  `valor_liquido_clinica` decimal(10,2) DEFAULT '0.00',
  `custo_protetico` decimal(10,2) NOT NULL DEFAULT '0.00',
  `comissao_dentista` decimal(10,2) DEFAULT '0.00',
  `observacoes` text COLLATE utf8mb4_unicode_ci,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `paciente_telefone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paciente_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_dentista` (`id_dentista`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimentos`
--

INSERT INTO `atendimentos` (`id`, `valor_total`, `data_atendimento`, `paciente_nome`, `id_dentista`, `taxa_cartao`, `valor_liquido_clinica`, `custo_protetico`, `comissao_dentista`, `observacoes`, `criado_em`, `paciente_telefone`, `paciente_email`) VALUES
(1, 450.00, '2026-02-01 14:27:15', 'FABRICIO DE SOUZA FARIAS', 2, 91.35, 113.65, 200.00, 45.00, NULL, '2026-02-01 17:27:15', '91992992812', 'fabriciosf@ufpa.br'),
(2, 150.00, '2026-02-01 14:29:05', 'Ana Lucia', 3, 0.00, 120.00, 0.00, 30.00, NULL, '2026-02-01 17:29:05', NULL, NULL),
(3, 300.00, '2026-02-01 14:30:48', 'Manoel Farias', 3, 3.00, 237.00, 0.00, 60.00, NULL, '2026-02-01 17:30:48', NULL, NULL),
(4, 225.00, '2026-02-01 14:32:11', 'Fernando Farias', 5, 0.00, 157.50, 0.00, 67.50, NULL, '2026-02-01 17:32:11', NULL, NULL),
(5, 225.00, '2026-02-02 10:04:15', 'Manoel Farias', 5, 4.75, 152.75, 0.00, 67.50, NULL, '2026-02-02 13:04:15', '91992992812', 'fabriciosf@ufpa.br'),
(6, 75.00, '2026-02-02 17:19:08', 'Elane José', 3, 8.12, 29.38, 0.00, 37.50, NULL, '2026-02-02 20:19:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_pagamentos`
--

DROP TABLE IF EXISTS `atendimento_pagamentos`;
CREATE TABLE IF NOT EXISTS `atendimento_pagamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `forma_pagamento` enum('dinheiro','pix','debito','credito') COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `qtd_parcelas` int DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `id_atendimento` (`id_atendimento`)
) ENGINE=MyISAM AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimento_pagamentos`
--

INSERT INTO `atendimento_pagamentos` (`id`, `id_atendimento`, `forma_pagamento`, `valor`, `qtd_parcelas`) VALUES
(1, 1, 'credito', 450.00, 10),
(2, 2, 'dinheiro', 150.00, 1),
(3, 3, 'debito', 300.00, 1),
(4, 4, 'dinheiro', 225.00, 1),
(5, 5, 'debito', 100.00, 1),
(6, 5, 'credito', 125.00, 1),
(7, 6, 'pix', 35.00, 1),
(8, 6, 'credito', 40.00, 10);

-- --------------------------------------------------------

--
-- Estrutura para tabela `atendimento_procedimentos`
--

DROP TABLE IF EXISTS `atendimento_procedimentos`;
CREATE TABLE IF NOT EXISTS `atendimento_procedimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_atendimento` int NOT NULL,
  `id_procedimento` int NOT NULL,
  `quantidade` int NOT NULL DEFAULT '1',
  `valor_procedimento` decimal(10,2) NOT NULL,
  `custo_protetico` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `atendimento_procedimentos`
--

INSERT INTO `atendimento_procedimentos` (`id`, `id_atendimento`, `id_procedimento`, `quantidade`, `valor_procedimento`, `custo_protetico`) VALUES
(1, 1, 5, 1, 450.00, 200.00),
(2, 2, 1, 1, 150.00, 0.00),
(3, 3, 6, 1, 150.00, 0.00),
(4, 3, 1, 1, 150.00, 0.00),
(5, 4, 2, 1, 75.00, 0.00),
(6, 4, 1, 1, 150.00, 0.00),
(7, 5, 1, 1, 150.00, 0.00),
(8, 5, 2, 1, 75.00, 0.00),
(9, 6, 2, 1, 75.00, 0.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `despesas`
--

DROP TABLE IF EXISTS `despesas`;
CREATE TABLE IF NOT EXISTS `despesas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `descricao` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `tipo` enum('fixa','variavel') COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_despesa` date NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `despesas`
--

INSERT INTO `despesas` (`id`, `descricao`, `valor`, `tipo`, `data_despesa`, `criado_em`) VALUES
(1, 'Aluguel', 1000.00, 'fixa', '2026-02-02', '2026-02-02 13:05:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `procedimentos`
--

DROP TABLE IF EXISTS `procedimentos`;
CREATE TABLE IF NOT EXISTS `procedimentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` enum('geral','especializado','protese') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descricao` text COLLATE utf8mb4_unicode_ci,
  `valor_base` decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `procedimentos`
--

INSERT INTO `procedimentos` (`id`, `nome`, `categoria`, `descricao`, `valor_base`) VALUES
(1, 'Limpeza', 'geral', NULL, 150.00),
(2, 'Manutenção Aparelho', 'especializado', NULL, 75.00),
(3, 'Manutenção Aparelho - Crédito', 'especializado', NULL, 80.00),
(5, 'Prótese Total', 'protese', NULL, 450.00),
(6, 'Extração', 'geral', NULL, 150.00),
(7, 'Clareamento', 'geral', NULL, 400.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `login` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `senha` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `perfil` enum('proprietario','recepcionista','dentista') COLLATE utf8mb4_unicode_ci NOT NULL,
  `criado_em` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `login`, `senha`, `perfil`, `criado_em`) VALUES
(1, 'Administrador', 'admin', '$2y$10$9aXFaAoUXEeS9L8Pc/gGXOz9YdBDsUFSP06cxyWIw2wHyXqq5Lc2W', 'proprietario', '2026-02-01 01:59:59'),
(2, 'Dr. Luciana Farias', 'luciana', '$2y$10$OCk/tInybfNZV2Gjzo5Lmu2If7WIoAw52pJD8h3sS5NDF7KcPNWuu', 'dentista', '2026-02-01 01:59:59'),
(3, 'Dra. Ana Costa', 'ana', '$2y$10$gKJX6WLlsVeum9/6Nf8W7OY8Akj/lgRPfDmkuI1FD3tKhwKn/HdZS', 'dentista', '2026-02-01 01:59:59'),
(4, 'Aline', 'aline', '$2y$10$m/5nQ.Ac3WfVmohmY0W0be.uMpooUhnfFiVXCuwLTebEdc6r3zUJC', 'recepcionista', '2026-02-01 02:18:40'),
(5, 'Dentista', 'dent', '$2y$10$8SoN12hTZ1K28pHTGjboE.9oOEeC.ljbP6IyEs9ebkKQPPCZcUAUi', 'dentista', '2026-02-01 14:55:21'),
(6, 'João', 'joao', '$2y$10$rA9I4hlqXmO/JQ2FIjZteusOImc4Bh/dVeMHudsLRgOxYsGa2bGr.', 'dentista', '2026-02-02 22:40:32');
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
