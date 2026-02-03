<?php
// views/novo_atendimento.php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once 'header.php';

// Busca dados para preencher os selects (Dentistas e Procedimentos)
try {
    $stmtDentistas = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'dentista'");
    $dentistas = $stmtDentistas->fetchAll();

    $stmtProc = $pdo->query("SELECT id, nome, categoria, valor_base FROM procedimentos");
    $procedimentos = $stmtProc->fetchAll();
} catch (Exception $e) {
    echo "<p class='error'>Erro ao carregar dados: " . $e->getMessage() . "</p>";
    $dentistas = []; $procedimentos = [];
}
?>

<div id="toast-notification" class="toast"></div>

<div class="card">
    <h2>Novo Lançamento de Atendimento</h2>
    <form action="<?= BASE_URL ?>actions/salvar_atendimento.php" method="POST">
        
        <div class="form-group">
            <label for="paciente">Nome do Paciente</label>
            <input type="text" name="paciente_nome" id="paciente_nome" required placeholder="Digite para buscar ou cadastrar..." autocomplete="off">
            <div id="paciente_sugestoes" class="sugestoes-box"></div>
        </div>

        <div class="form-group">
            <label for="paciente_telefone">Telefone (Opcional)</label>
            <input type="text" name="paciente_telefone" id="paciente_telefone" placeholder="(XX) XXXXX-XXXX">
        </div>

        <div class="form-group">
            <label for="paciente_email">E-mail (Opcional)</label>
            <input type="text" name="paciente_email" id="paciente_email" placeholder="email@exemplo.com">
        </div>

        <div class="form-group">
            <label for="dentista">Dentista Responsável</label>
            <select name="id_dentista" id="dentista" required>
                <option value="">Selecione...</option>
                <?php foreach($dentistas as $d): ?>
                    <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div id="procedimentos_container">
            <!-- As linhas de procedimento serão adicionadas aqui -->
        </div>

        <div class="form-group">
            <button type="button" id="add_procedimento" class="btn btn-info">Adicionar Procedimento</button>
        </div>

        <div class="form-group">
            <label for="valor">Valor Bruto Total (R$)</label>
            <input type="number" step="0.01" id="valor" required readonly placeholder="0.00">
        </div>
        
        <div id="pagamentos_container">
            <!-- As linhas de pagamento serão adicionadas aqui -->
        </div>

        <div class="form-group">
            <button type="button" id="add_pagamento" class="btn btn-info">Adicionar Pagamento</button>
        </div>

        <div class="form-group">
            <p>Total Pago: <span id="total_pago">R$ 0,00</span></p>
            <p>Restante a Pagar: <span id="restante_pagar">R$ 0,00</span></p>
        </div>

        <button type="submit" class="btn btn-success" style="width: 100%;">Lançar Atendimento</button>
    </form>
</div>

<style>
    .procedimento-row, .pagamento-row {
        display: flex;
        align-items: center;
        margin-bottom: 10px;
    }
    .procedimento-row select, .procedimento-row input, .pagamento-row select, .pagamento-row input {
        margin-right: 10px;
    }
    .sugestoes-box {
        border: 1px solid #ccc;
        max-height: 150px;
        overflow-y: auto;
        display: none;
        background: white;
        position: absolute;
        width: calc(100% - 2rem - 2px);
        z-index: 1000;
    }
    .sugestao-item {
        padding: 10px;
        cursor: pointer;
    }
    .sugestao-item:hover {
        background-color: #f0f0f0;
    }
    .toast {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-size: 16px;
        z-index: 9999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.5s, visibility 0.5s, transform 0.5s;
        transform: translateX(100%);
    }
    .toast.show {
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }
    .toast.error {
        background-color: #c0392b; /* red */
    }
    .toast.success { background-color: #27ae60; }
</style>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const procedimentos = <?= json_encode($procedimentos) ?>;
    const procContainer = document.getElementById('procedimentos_container');
    const addProcButton = document.getElementById('add_procedimento');
    const valorTotalInput = document.getElementById('valor');

    const pagamentosContainer = document.getElementById('pagamentos_container');
    const addPagamentoButton = document.getElementById('add_pagamento');
    const totalPagoSpan = document.getElementById('total_pago');
    const restantePagarSpan = document.getElementById('restante_pagar');

    function createProcedimentoRow() {
        const row = document.createElement('div');
        row.classList.add('procedimento-row');

        const select = document.createElement('select');
        select.name = 'procedimentos[id][]';
        select.required = true;
        
        const option = document.createElement('option');
        option.value = '';
        option.textContent = 'Selecione...';
        select.appendChild(option);

        procedimentos.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.nome} (${p.categoria})`;
            opt.dataset.valor = p.valor_base;
            opt.dataset.categoria = p.categoria;
            select.appendChild(opt);
        });

        const quantidadeInput = document.createElement('input');
        quantidadeInput.type = 'number';
        quantidadeInput.name = 'procedimentos[quantidade][]';
        quantidadeInput.min = 1;
        quantidadeInput.value = 1;
        quantidadeInput.required = true;
        quantidadeInput.style.width = '80px';

        const custoProteticoInput = document.createElement('input');
        custoProteticoInput.type = 'number';
        custoProteticoInput.name = 'procedimentos[custo_protetico][]';
        custoProteticoInput.step = '0.01';
        custoProteticoInput.placeholder = 'Custo Protético (R$)';
        custoProteticoInput.style.display = 'none';
        custoProteticoInput.style.width = '150px';

        const valorPersonalizadoInput = document.createElement('input');
        valorPersonalizadoInput.type = 'number';
        valorPersonalizadoInput.name = 'procedimentos[valor_personalizado][]';
        valorPersonalizadoInput.step = '0.01';
        valorPersonalizadoInput.placeholder = 'Novo Valor (R$)';
        valorPersonalizadoInput.style.width = '120px';
        valorPersonalizadoInput.disabled = true; // Começa desabilitado

        const alterarValorButton = document.createElement('button');
        alterarValorButton.type = 'button';
        alterarValorButton.textContent = 'Alterar Valor';
        alterarValorButton.classList.add('btn', 'btn-info');
        alterarValorButton.style.marginLeft = '5px';
        alterarValorButton.addEventListener('click', () => {
            valorPersonalizadoInput.disabled = !valorPersonalizadoInput.disabled;
            if (valorPersonalizadoInput.disabled) {
                valorPersonalizadoInput.value = ''; // Limpa o valor ao desabilitar
            } else {
                valorPersonalizadoInput.focus();
            }
            updateTotal();
        });


        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remover';
        removeButton.classList.add('btn', 'btn-danger');
        removeButton.addEventListener('click', () => {
            row.remove();
            updateTotal();
        });

        row.appendChild(select);
        row.appendChild(quantidadeInput);
        row.appendChild(custoProteticoInput);
        row.appendChild(alterarValorButton);
        row.appendChild(valorPersonalizadoInput);
        row.appendChild(removeButton);

        select.addEventListener('change', () => {
            updateTotal();
            
            // Limpa o valor customizado e desabilita o campo quando o procedimento muda
            valorPersonalizadoInput.value = '';
            valorPersonalizadoInput.disabled = true;

            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption && selectedOption.dataset.categoria === 'protese') {
                custoProteticoInput.style.display = 'inline-block';
                custoProteticoInput.required = true;
            } else {
                custoProteticoInput.style.display = 'none';
                custoProteticoInput.required = false;
                custoProteticoInput.value = '';
            }
        });
        quantidadeInput.addEventListener('input', updateTotal);
        valorPersonalizadoInput.addEventListener('input', updateTotal);

        return row;
    }

    function createPagamentoRow() {
        const row = document.createElement('div');
        row.classList.add('pagamento-row');

        const select = document.createElement('select');
        select.name = 'pagamentos[forma][]';
        select.required = true;
        
        const formas = ['dinheiro', 'pix', 'debito', 'credito'];
        formas.forEach(f => {
            const opt = document.createElement('option');
            opt.value = f;
            opt.textContent = f.charAt(0).toUpperCase() + f.slice(1);
            select.appendChild(opt);
        });

        const valorInput = document.createElement('input');
        valorInput.type = 'number';
        valorInput.name = 'pagamentos[valor][]';
        valorInput.step = '0.01';
        valorInput.required = true;
        valorInput.placeholder = 'Valor';
        
        const parcelasSelect = document.createElement('select');
        parcelasSelect.name = 'pagamentos[parcelas][]';
        parcelasSelect.style.display = 'none';
        for (let i = 1; i <= 12; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.textContent = `${i}x`;
            parcelasSelect.appendChild(opt);
        }

        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.textContent = 'Remover';
        removeButton.classList.add('btn', 'btn-danger');
        removeButton.addEventListener('click', () => {
            row.remove();
            updatePagamentos();
        });

        row.appendChild(select);
        row.appendChild(valorInput);
        row.appendChild(parcelasSelect);
        row.appendChild(removeButton);

        select.addEventListener('change', () => {
            parcelasSelect.style.display = select.value === 'credito' ? 'block' : 'none';
        });

        valorInput.addEventListener('input', updatePagamentos);

        return row;
    }

    function updateTotal() {
        let total = 0;
        const rows = procContainer.querySelectorAll('.procedimento-row');
        rows.forEach(row => {
            const select = row.querySelector('select[name="procedimentos[id][]"]');
            const quantidadeInput = row.querySelector('input[name="procedimentos[quantidade][]"]');
            const valorPersonalizadoInput = row.querySelector('input[name="procedimentos[valor_personalizado][]"]');
            
            const selectedOption = select.options[select.selectedIndex];
            const quantidade = quantidadeInput ? parseInt(quantidadeInput.value) : 0;
            
            if (selectedOption && selectedOption.dataset.valor && quantidade > 0) {
                let valorProcedimento = parseFloat(selectedOption.dataset.valor);

                // Se o campo de valor personalizado estiver habilitado e preenchido, use-o
                if (valorPersonalizadoInput && !valorPersonalizadoInput.disabled && valorPersonalizadoInput.value !== '') {
                    valorProcedimento = parseFloat(valorPersonalizadoInput.value);
                }
                
                total += valorProcedimento * quantidade;
            }
        });
        valorTotalInput.value = total.toFixed(2);
        updatePagamentos();
    }

    function updatePagamentos() {
        let totalPago = 0;
        const rows = pagamentosContainer.querySelectorAll('.pagamento-row');
        rows.forEach(row => {
            const valor = row.querySelector('input[type="number"]').value;
            if (valor) {
                totalPago += parseFloat(valor);
            }
        });

        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        const restante = valorTotal - totalPago;

        totalPagoSpan.textContent = `R$ ${totalPago.toFixed(2)}`;
        restantePagarSpan.textContent = `R$ ${restante.toFixed(2)}`;

        if (restante.toFixed(2) == 0) {
            restantePagarSpan.style.color = 'green';
        } else {
            restantePagarSpan.style.color = 'red';
        }
    }

    addProcButton.addEventListener('click', () => {
        procContainer.appendChild(createProcedimentoRow());
    });
    
    addPagamentoButton.addEventListener('click', () => {
        pagamentosContainer.appendChild(createPagamentoRow());
    });

    // Adiciona uma linha de procedimento por padrão
    procContainer.appendChild(createProcedimentoRow());
    // Adiciona uma linha de pagamento por padrão
    pagamentosContainer.appendChild(createPagamentoRow());
    updateTotal();
    updatePagamentos();

    // --- Toast Notification ---
    const toast = document.getElementById('toast-notification');
    function showToast(message, type = 'error') {
        toast.textContent = message;
        toast.className = 'toast show ' + type;

        setTimeout(() => {
            toast.className = toast.className.replace('show', '');
        }, 5000); // Hide after 5 seconds
    }

    // --- Form Submission via Fetch ---
    const form = document.querySelector('form');
    form.addEventListener('submit', async function(event) {
        event.preventDefault(); // Prevent normal submission

        const submitButton = form.querySelector('button[type="submit"]');

        // --- Validações do lado do cliente ---
        const valorTotal = parseFloat(valorTotalInput.value) || 0;
        let totalPago = 0;
        pagamentosContainer.querySelectorAll('.pagamento-row').forEach(row => {
            const valor = row.querySelector('input[name="pagamentos[valor][]"]').value;
            if (valor) {
                totalPago += parseFloat(valor);
            }
        });

        if (valorTotal <= 0) {
            showToast('O valor total do atendimento deve ser maior que zero.', 'error');
            return; // Impede o envio
        }

        if (Math.abs(valorTotal - totalPago) > 0.01) {
            showToast(`A soma dos pagamentos (R$ ${totalPago.toFixed(2)}) não corresponde ao valor total (R$ ${valorTotal.toFixed(2)}).`, 'error');
            return; // Impede o envio
        }
        // --- Fim das validações ---


        submitButton.disabled = true;
        submitButton.textContent = 'Salvando...';

        const formData = new FormData(form);

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.sucesso) {
                showToast(result.mensagem, 'success');
                setTimeout(() => {
                    window.location.href = result.redirectUrl || '<?= BASE_URL ?>index.php';
                }, 1500);
            } else {
                showToast(result.erro || 'Ocorreu um erro desconhecido.', 'error');
                submitButton.disabled = false;
                submitButton.textContent = 'Lançar Atendimento';
            }
        } catch (error) {
            console.error('Fetch Error:', error);
            showToast('Ocorreu um erro de comunicação. Verifique o console para detalhes.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = 'Lançar Atendimento';
        }
    });

    // --- Autocomplete do Paciente ---
    const pacienteInput = document.getElementById('paciente_nome');
    const sugestoesBox = document.getElementById('paciente_sugestoes');
    const telefoneInput = document.getElementById('paciente_telefone');
    const emailInput = document.getElementById('paciente_email');

    pacienteInput.addEventListener('input', async function() {
        const term = this.value;

        if (term.length < 2) {
            sugestoesBox.innerHTML = '';
            sugestoesBox.style.display = 'none';
            return;
        }

        try {
            // Usando a constante BASE_URL do PHP para a URL da action
            const response = await fetch(`<?= BASE_URL ?>actions/buscar_paciente.php?term=${encodeURIComponent(term)}`);
            const pacientes = await response.json();

            sugestoesBox.innerHTML = '';
            if (pacientes.length > 0) {
                sugestoesBox.style.display = 'block';
                pacientes.forEach(paciente => {
                    const div = document.createElement('div');
                    div.classList.add('sugestao-item');
                    div.textContent = paciente.nome;
                    div.addEventListener('click', () => {
                        pacienteInput.value = paciente.nome;
                        telefoneInput.value = paciente.telefone || '';
                        emailInput.value = paciente.email || '';
                        sugestoesBox.style.display = 'none';
                    });
                    sugestoesBox.appendChild(div);
                });
            } else {
                sugestoesBox.style.display = 'none';
            }
        } catch (error) {
            console.error('Erro ao buscar pacientes:', error);
            sugestoesBox.style.display = 'none';
        }
    });

    // Opcional: Esconder sugestões se o usuário clicar fora do campo/caixa
    document.addEventListener('click', function(e) {
        if (e.target !== pacienteInput && !sugestoesBox.contains(e.target)) {
            sugestoesBox.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>