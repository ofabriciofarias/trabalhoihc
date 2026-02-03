    </main> <!-- Fim .container -->

    <footer style="text-align: center; padding: 2rem 0; margin-top: 2rem; border-top: 1px solid var(--border-color); color: var(--secondary-color);">
        <p>&copy; <?= date('Y') ?> Clínica Prev Dentista. Todos os direitos reservados.</p>
    </footer>

    <script>
        // Garante que o script rode após o DOM carregar
        document.addEventListener('DOMContentLoaded', function() {
            const selectPagamento = document.getElementById('forma_pagamento');
            const divParcelas = document.getElementById('div_parcelas');
            
            if(selectPagamento) {
                selectPagamento.addEventListener('change', function() {
                    divParcelas.style.display = (this.value === 'credito') ? 'block' : 'none';
                });

                // Garante estado inicial correto no carregamento da página
                divParcelas.style.display = (selectPagamento.value === 'credito') ? 'block' : 'none';
            }
        });
    </script>
</body>
</html>
