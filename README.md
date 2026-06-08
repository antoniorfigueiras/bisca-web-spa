# 🌐 Bisca Game Platform (Web & Backend SPA)

Plataforma Web baseada numa arquitetura distribuída e desenvolvida como uma **Single-Page Application (SPA)** para a unidade curricular de **Desenvolvimento de Aplicações Distribuídas (DAD)**. 

O sistema descentraliza a interface no cliente e centraliza toda a lógica de negócio, controlo de estado de jogo e persistência no servidor, atuando como a *Single Source of Truth*.

---

## 🛠️ Stack Tecnológica & Arquitetura

O ecossistema é composto por três pilares fundamentais, todos assentes em tecnologias open-source e compatíveis com a infraestrutura escolar:

* **Frontend (SPA):** Desenvolvido obrigatoriamente em **Vue.js**, focado numa interface altamente reativa e adaptada para browsers desktop na intranet da ESTG.
* **API RESTful:** Servidor backend responsável pela gestão de utilizadores, autenticação, histórico e transações económicas (moedas).
* **Servidor WebSockets:** Desenvolvido em **Bun Server**, gerindo de forma síncrona e em tempo real o estado de cada partida de Bisca.
* **Base de Dados:** Persistência relacional robusta utilizando **MySQL / PostgreSQL**.

---

## 🏗️ Infraestrutura & DevOps (Kubernetes)

O deployment da aplicação segue as diretrizes rigorosas de infraestrutura e virtualização (Conformidade NF1) no cluster corporativo da instituição:

### 1. Engenharia de Contentores (Docker)
A aplicação foi modularizada em múltiplos contentores independentes:
* **Nginx:** Configurado especificamente para servir o *build* de produção do cliente Vue.js.
* **Laravel / Node.js:** Contentor dedicado para expor os endpoints da API RESTful.
* **Bun Server:** Engine em runtime de alta performance focado na latência mínima para WebSockets.
* **MySQL:** Base de dados relacional isolada.

### 2. Fluxo de Deploy & Orquestração
O ciclo de publicação segue os tutoriais oficiais da plataforma [DAD Tutorials](https://dad-tutorials.vercel.app/):
1.  **Containerization:** Configuração dos respetivos `Dockerfiles` e `.dockerignore`.
2.  **Registry Push:** Construção e envio das imagens otimizadas para o Docker Registry da escola.
3.  **Kubernetes Orchestration:** Implementação, mapeamento de portas e publicação dos recursos (Pods, Services, Ingress) no Cluster Kubernetes da ESTG.

---

## 📂 Estrutura de Pastas do Ecossistema

```text
├── frontend/          # Cliente SPA em Vue.js (Nginx em Produção)
├── api/               # Servidor RESTful API (Laravel / Express)
├── websockets/        # Motor de jogo em tempo real (Bun Server)
├── deployment/        # Manifesto YAML do Kubernetes (Ingress, Services, Pods)
└── README.md          # Documentação técnica do sistema
