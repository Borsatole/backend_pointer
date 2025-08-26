-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 20/05/2025 às 16:24
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `sistemacomauth`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `codigosderecargas`
--

CREATE TABLE `codigosderecargas` (
  `id` int(11) NOT NULL,
  `idRecarga` int(11) NOT NULL,
  `servidor` varchar(50) NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `usado` tinyint(1) DEFAULT 0,
  `dias` varchar(4) NOT NULL,
  `last_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `codigosderecargas`
--

INSERT INTO `codigosderecargas` (`id`, `idRecarga`, `servidor`, `codigo`, `usado`, `dias`, `last_update`) VALUES
(1, 7, 'unitv', 'UNITV-CODE-003', 0, '30', '2025-04-09 13:25:55'),
(5, 3, 'unitv', 'UNITV-CODE-005', 0, '90', '2025-04-09 13:58:35'),
(38, 4, 'alphaplay', 'ALPHAA-CODIGO-TESTE', 1, '30', '2025-05-05 20:31:14'),
(42, 4, 'alphaplay', 'aaaaaaa', 1, '30', '2025-05-05 20:36:40'),
(43, 4, 'alphaplay', 'bbbbbbbbbbb', 0, '30', '2025-05-05 20:31:40'),
(44, 4, 'alphaplay', 'cccccccccc', 0, '30', '2025-05-05 20:31:43'),
(45, 4, 'alphaplay', 'ddddddddddddd', 0, '30', '2025-05-05 20:31:45'),
(46, 4, 'alphaplay', 'eeeeeeeeeeeeeeee', 0, '30', '2025-05-05 20:31:47'),
(47, 4, 'alphaplay', 'ffffffffffffff', 0, '30', '2025-05-05 20:31:50'),
(48, 4, 'alphaplay', 'gggggggggggggg', 0, '30', '2025-05-05 20:31:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `mp_token` varchar(250) NOT NULL,
  `chavesecretanotificacao` varchar(500) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`mp_token`, `chavesecretanotificacao`) VALUES
('APP_USR-558360857384760-022615-8f5d3a53f6f28bef3e5e54a5ae3b689a-362655224', 'bbae26406d869fd2c6f79cadf3aba383f12794c275e88c2a84cc95e80c97d472');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cupons`
--

CREATE TABLE `cupons` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `desconto` decimal(10,2) NOT NULL,
  `tipo` enum('percent','valor') NOT NULL,
  `validade` datetime NOT NULL,
  `maxuse` int(11) NOT NULL DEFAULT 1,
  `usos` int(11) NOT NULL DEFAULT 0,
  `valido` tinyint(1) NOT NULL DEFAULT 1,
  `produtos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`produtos`)),
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `cupons`
--

INSERT INTO `cupons` (`id`, `codigo`, `desconto`, `tipo`, `validade`, `maxuse`, `usos`, `valido`, `produtos`, `created`, `updated`) VALUES
(57, 'DESCONTAO41', 95.00, 'percent', '2025-06-15 12:01:00', 5, 2, 1, '[\"4\",\"7\",\"55\",\"56\",\"57\",\"58\",\"11\",\"3\"]', '2025-03-29 22:11:30', '2025-05-03 17:16:24'),
(58, 'BLACKWEEK', 10.00, 'valor', '2025-11-29 23:59:00', 2, 1, 1, '[\"4\",\"7\",\"5\"]', '2025-03-29 22:15:45', '2025-04-09 13:40:54'),
(61, 'VIPEXCLUSIVO', 95.00, 'percent', '2025-07-01 00:00:00', 3, 0, 1, '[\"7\",\"3\"]', '2025-03-29 22:22:55', '2025-04-09 11:24:07'),
(63, 'GANHE51', 50.00, 'percent', '2025-12-31 23:59:00', 8, 0, 1, '[\"4\",\"7\"]', '2025-03-29 22:28:10', '2025-04-24 15:39:12'),
(64, 'ULTIMACHANCE', 40.00, 'percent', '2025-04-30 23:59:00', 7, 0, 1, '[]', '2025-03-29 22:30:20', '2025-04-14 17:13:31');

-- --------------------------------------------------------

--
-- Estrutura para tabela `otp_registro`
--

CREATE TABLE `otp_registro` (
  `id` int(11) NOT NULL,
  `codigo` varchar(4) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip` varchar(45) NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `otp_registro`
--

INSERT INTO `otp_registro` (`id`, `codigo`, `email`, `ip`, `criado_em`) VALUES
(150, '1585', 'usuario1@admin.com', '::1', '2025-04-29 22:11:54'),
(151, '8167', 'usuario3@admin.com', '::1', '2025-04-29 22:33:32'),
(161, '0233', 'conectboxtecnologia2@gmail.com', '::1', '2025-05-01 21:02:49'),
(163, '1681', 'borsatole@gmail.com', '::1', '2025-05-03 10:03:34'),
(178, '1810', 'conectboxtecnologia@gmail.com', '::1', '2025-05-03 14:14:19');

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `idpedido` varchar(30) NOT NULL,
  `idusuario` int(11) NOT NULL,
  `idproduto` int(11) NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `status` varchar(50) NOT NULL,
  `codigoderecarga` varchar(250) NOT NULL,
  `servidor` varchar(50) NOT NULL,
  `dias` varchar(4) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `created` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recargas`
--

CREATE TABLE `recargas` (
  `id` int(11) NOT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `titulo` varchar(255) NOT NULL,
  `servidor` varchar(50) NOT NULL,
  `dias` varchar(4) NOT NULL,
  `valor` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `recargas`
--

INSERT INTO `recargas` (`id`, `imagem`, `titulo`, `servidor`, `dias`, `valor`) VALUES
(3, 'unitv.png', 'Unitv 90 Dias', 'unitv', '90', 90.00),
(4, '68097a9cabe3f.png', 'Alphaplay 30 Dias', 'alphaplay', '30', 30.00),
(7, 'unitv.png', 'Unitv 30 Dias', 'unitv', '30', 31.00),
(11, '68046194127ff.png', 'Unitv 60 Dias', 'unitv', '60', 60.00),
(55, '680a319d8f97a.png', 'YOUTUBE MUSIC PREMIUM 30 DIAS', 'Youtube', '30', 15.00),
(56, '680a32b1cb336.webp', 'SPOTIFY PREMIUM 30 DIAS	', 'Spotify', '30', 14.50),
(57, '680a3325025b9.png', 'PRIME VIDEO 30 DIAS	', 'Prime Video', '30', 20.00),
(58, '680a33594d581.png', 'PRIME VIDEO 60 DIAS	', 'Prime Video', '30', 30.00);

-- --------------------------------------------------------

--
-- Estrutura para tabela `tentativas_login`
--

CREATE TABLE `tentativas_login` (
  `id` int(11) NOT NULL,
  `ip` varchar(50) NOT NULL,
  `tentativas` int(11) DEFAULT 0,
  `ultima_tentativa` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tentativas_login`
--

INSERT INTO `tentativas_login` (`id`, `ip`, `tentativas`, `ultima_tentativa`) VALUES
(98, '::1', 1, '2025-05-05 20:29:12');

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(250) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `nivel` varchar(10) NOT NULL,
  `telefone` varchar(20) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `avatar` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `nivel`, `telefone`, `avatar`) VALUES
(1, 'Leandro Admin', 'usuario1@admin.com', 'admin', 'admin', '14997172257', 'avatar1.png'),
(2, 'Vitoria Sabrina Botacini', 'usuario2@admin.com', 'admin', 'padrao', '14997131020', 'avatar1.png'),
(39, 'Roberio dos teclados', 'conectboxtecnologia@gmail.com', '12#34$56', 'padrao', '11999999999', 'avatar1.png');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `codigosderecargas`
--
ALTER TABLE `codigosderecargas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `cupons`
--
ALTER TABLE `cupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Índices de tabela `otp_registro`
--
ALTER TABLE `otp_registro`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`idpedido`),
  ADD KEY `idusuario` (`idusuario`);

--
-- Índices de tabela `recargas`
--
ALTER TABLE `recargas`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `codigosderecargas`
--
ALTER TABLE `codigosderecargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `cupons`
--
ALTER TABLE `cupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT de tabela `otp_registro`
--
ALTER TABLE `otp_registro`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=179;

--
-- AUTO_INCREMENT de tabela `recargas`
--
ALTER TABLE `recargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=99;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`idusuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
