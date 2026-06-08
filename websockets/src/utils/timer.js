// Ficheiro: src/utils/timer.js

/**
 * Utilitários para gestão de temporizadores de jogo.
 * G3: Implementação do limite de 20 segundos (NF5).
 */

/**
 * Inicia ou reinicia o temporizador de turno.
 * @param {Object} game - Estado do jogo.
 * @param {Function} callback - Função a executar quando o tempo acaba (handleGameTimeout).
 * @param {Number} duration - Duração em milissegundos (Padrão: 20000ms = 20s).
 */
export const setGameTimeout = (game, callback, duration = 20000) => {
    // Interrompe a execução se a referência do jogo não for válida
    if (!game) return;

    // 1. Limpeza de segurança: Se já existir um timer a correr, cancela-o para evitar sobreposição de processos
    if (game.timer) {
        clearTimeout(game.timer);
    }

    // 2. Configuração da propriedade 'timer' no objeto game.
    // enumerable: false é CRÍTICO para o Socket.IO não tentar enviar o objeto Timer para o cliente (evita erros de serialização)
    if (!Object.getOwnPropertyDescriptor(game, 'timer')) {
        Object.defineProperty(game, "timer", {
            writable: true,
            enumerable: false, // Oculto da serialização JSON para segurança e performance
            configurable: true,
            value: null
        });
    }

    // 3. Define o prazo absoluto (timestamp) para que o Frontend possa renderizar a contagem decrescente sincronizada
    game.turnDeadline = Date.now() + duration;

    // 4. Inicia o novo temporizador que aguardará a duração especificada antes de aplicar a penalização
    game.timer = setTimeout(() => {
        try {
            // Normalização do status para garantir que a lógica de timeout só se aplica a jogos em curso
            const currentStatus = game.status ? game.status.toUpperCase() : "";

            // Verifica se o estado do jogo ainda permite a aplicação de timeout (evita penalizar jogos já encerrados)
            if (currentStatus === "PLAYING" || currentStatus === "PL") {
                console.log(`⚡ [TIMER ACTION] Timeout no jogo ${game.id}. A aplicar penalização...`);

                // Limpa os estados do temporizador antes de executar a lógica de callback
                game.turnDeadline = null;
                game.timer = null;

                // Executa a injeção de dependência (geralmente handleGameTimeout) para resolver a desistência forçada
                if (typeof callback === 'function') {
                    callback(game);
                } else {
                    console.error(`❌ [TIMER ERROR] Callback inválido no jogo ${game.id}.`);
                }
            }
        } catch (error) {
            // Captura erros inesperados para evitar que uma falha no timer derrube o processo do servidor
            console.error(`❌ [TIMER CRASH] Erro crítico ao processar timeout do jogo ${game.id}:`, error);
        }
    }, duration);
};

/**
 * Cancela o temporizador de forma limpa.
 * Deve ser chamado sempre que uma jogada é feita ou o jogo termina para libertar recursos de memória.
 * @param {Object} game - Estado do jogo.
 */
export const clearGameTimeout = (game) => {
    if (game) {
        // Interrompe a contagem regressiva ativa no motor do Node.js
        if (game.timer) {
            clearTimeout(game.timer);
            game.timer = null;
        }
        // Remove a marca de tempo para sinalizar ao frontend que o turno foi processado
        game.turnDeadline = null;
    }
};