import { createServer } from "http";
import { Server } from "socket.io";
import dotenv from "dotenv";

// Carrega as configurações do ficheiro .env para o process.env do Node.js
dotenv.config();

// Importar os Handlers (Gestores de Eventos)
import { handleConnection } from "./src/handlers/connectionHandler.js";
import { handleGameEvents } from "./src/handlers/gameHandler.js";

/**
 * 1. Configuração do Servidor HTTP
 * Inclui um Health Check mais robusto para monitorização.
 * Serve para validar se o serviço está online sem necessidade de estabelecer uma ligação via Socket.
 */
const httpServer = createServer((req, res) => {
  // Rota de monitorização (Health Check) para ferramentas de orquestração ou uptime
  if (req.url === "/health" || req.url === "/") {
    res.writeHead(200, { "Content-Type": "application/json" });
    res.end(
      JSON.stringify({
        status: "online",
        service: "Bisca Socket Server",
        timestamp: new Date().toISOString(),
      })
    );
    return;
  }

  // Resposta padrão para rotas inexistentes
  res.writeHead(404);
  res.end();
});

/**
 * 2. Configuração do Socket.IO
 * Adicionada configuração de CORS via variável de ambiente para produção.
 */
const io = new Server(httpServer, {
  cors: {
    origin: [
      "http://web-dad-group-12-172.22.21.253.sslip.io",
    ],
    methods: ["GET", "POST"],
    credentials: true, 
  },
  pingTimeout: 60000,
  pingInterval: 25000,
});

/**
 * 3. Gestão Global de Eventos e Middleware
 * Ponto de entrada para todas as ligações de clientes WebSocket.
 */
io.on("connection", (socket) => {
  // Regista metadados da ligação para fins de auditoria e segurança
  const clientIp = socket.handshake.address;
  console.log(`[CONN] ⚡ Cliente: ${socket.id} | IP: ${clientIp}`);

  // Delegar a gestão de eventos para módulos específicos para manter o código organizado
  try {
    // Gere o ciclo de vida da ligação e identificação do utilizador
    handleConnection(io, socket);
    // Gere toda a lógica de interação com as partidas (jogadas, criação, etc.)
    handleGameEvents(io, socket);
  } catch (error) {
    // Em caso de erro na inicialização, a ligação é terminada por segurança
    console.error(
      `[ERR] Falha ao inicializar handlers para ${socket.id}:`,
      error
    );
    socket.disconnect();
  }

  // Captura e regista erros específicos do socket individual para depuração
  socket.on("error", (err) => {
    console.error(`[ERR] Socket Error (${socket.id}):`, err.message);
  });
});

/**
 * 4. Inicialização do Servidor
 * Define o porto e o host, garantindo que o servidor escuta em todas as interfaces de rede.
 */
const PORT = process.env.SOCKET_PORT || 3000;
const HOST = "0.0.0.0";

httpServer.listen(PORT, HOST, () => {
  console.log("--------------------------------------------------");
  console.log(`🚀 Bisca Server Running on: http://localhost:${PORT}`);
  console.log(`📡 CORS Origin: ${process.env.CLIENT_URL || "*"}`);
  console.log("--------------------------------------------------");
});

/**
 * Tratamento de fecho gracioso (Graceful Shutdown)
 * Garante que o servidor termina as ligações ativas e limpa os recursos antes de desligar.
 */
process.on("SIGTERM", () => {
  console.log("SIGTERM recebido. A fechar servidor Socket.IO...");
  // Fecha o motor de sockets e depois o servidor HTTP
  io.close(() => {
    httpServer.close(() => {
      console.log("Servidor encerrado.");
      process.exit(0);
    });
  });
});
