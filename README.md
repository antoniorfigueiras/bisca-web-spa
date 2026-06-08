# 🃏 Bisca Game Platform

Plataforma Web (Single-Page Application) desenvolvida para o projeto da unidade curricular de **Desenvolvimento de Aplicações Distribuídas (DAD)**. O sistema permite jogar o tradicional jogo de cartas português "Bisca", com suporte para modos single-player (vs Bot) e multiplayer (WebSockets).

## 🚀 Funcionalidades Principais

* **Modos de Jogo:** Bisca de 3 e Bisca de 9 cartas.
* **Multiplayer em Tempo Real:** Comunicação bidirecional via WebSockets, garantindo consistência no estado do jogo.
* **Sistema de Economia:** Gestão de moedas (coins) para entrada em partidas, apostas customizáveis e histórico de transações.
* **Estatísticas e Leaderboards:** Tabelas de classificação globais e histórico detalhado de partidas para utilizadores registados.
* **Painel Administrativo:** Gestão de utilizadores, histórico de transações e estatísticas da plataforma.

## 🛠 Stack Tecnológica

| Componente | Tecnologia |
| :--- | :--- |
| **Frontend** | Vue.js (SPA) |
| **Backend API** | Node.js / Laravel |
| **Tempo Real** | WebSockets |
| **Base de Dados** | PostgreSQL |
| **Pagamentos** | Integração com Gateway Externo (Simulado) |

## 🏗 Arquitetura do Sistema

O projeto segue uma arquitetura orientada a eventos para garantir que o servidor atua como a *Single Source of Truth* durante as partidas.

* **Client-Side:** O Vue.js gere a interface reativa, comunicando as ações do utilizador e recebendo atualizações de estado via Socket.
* **Server-Side:** O motor de jogo processa a lógica de jogo, validação de regras e persistência de dados.

## 📋 Pré-requisitos para Execução

1. Node.js (v18+)
2. Servidor PostgreSQL
3. Servidor Laravel (para a API de suporte)

```bash
# Instalação das dependências
npm install

# Iniciar o ambiente de desenvolvimento
npm run dev
