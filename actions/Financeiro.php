<?php
// actions/Financeiro.php

class Financeiro {

    // Taxas da Maquininha (configuradas conforme roteiro)
    const TAXA_DEBITO = 0.01; // 1%
    const TAXA_CREDITO_AVISTA = 0.03; // 3%
    const TAXA_JUROS_2_6 = 0.0185; // 1.85% ao mês
    const TAXA_JUROS_7_10 = 0.0173; // 1.73% ao mês

    // --- Percentuais de Repasse (Comissão) ---
    // Regra de comissão geral com meta
    const COMISSAO_GERAL_BASE = 0.20; // 20% (antes da meta)
    const COMISSAO_GERAL_BONUS = 0.30; // 30% (após atingir a meta)
    const META_FATURAMENTO_GERAL = 10000.00; // R$ 10.000,00
    const COMISSAO_ESPECIALIZADO = 0.50; // 50%
    const COMISSAO_PROTESE_DENTISTA = 0.10; // 10% do valor do procedimento vai para o Dentista

    /**
     * Calcula o valor líquido que entra no caixa da clínica após taxas da maquininha.
     */
    public static function calcularLiquidoMaquininha($valorBruto, $formaPagamento, $qtdParcelas = 1) {
        $taxa = 0.0;

        if ($formaPagamento === 'debito') {
            $taxa = self::TAXA_DEBITO;
        } elseif ($formaPagamento === 'credito') {
            if ($qtdParcelas <= 1) {
                $taxa = self::TAXA_CREDITO_AVISTA;
            } elseif ($qtdParcelas <= 6) {
                // Taxa base + (Juros ao mês * Parcelas)
                $taxa = self::TAXA_CREDITO_AVISTA + ($qtdParcelas * self::TAXA_JUROS_2_6);
            } else {
                // Faixa de 7 a 10 (ou mais)
                $taxa = self::TAXA_CREDITO_AVISTA + ($qtdParcelas * self::TAXA_JUROS_7_10);
            }
        } else {
            // Dinheiro ou PIX
            $taxa = 0.0;
        }

        $valorTaxa = $valorBruto * $taxa;
        return [
            'valor_taxa' => $valorTaxa,
            'valor_liquido' => $valorBruto - $valorTaxa,
            'taxa_aplicada_percentual' => $taxa * 100
        ];
    }

    /**
     * Calcula a divisão do valor (Split) baseado na categoria do procedimento.
     * O cálculo é feito sobre o VALOR BRUTO (conforme roteiro: "30% do Valor Bruto").
     * @param float $valorBruto O valor bruto do procedimento.
     * @param string $categoria A categoria do procedimento ('geral', 'especializado', 'protese').
     * @param float $faturamentoBrutoMensal O faturamento bruto do mês até o momento para aplicar a regra de comissão.
     * @param float $custoProteticoManual Valor em reais do custo do protético (apenas para prótese).
     */
    public static function calcularComissao($valorBruto, $categoria, $faturamentoBrutoMensal = 0, $custoProteticoManual = 0.0) {
        $comissaoDentista = 0.0;
        $custoProteseLab = 0.0; // Inicializa

        switch ($categoria) {
            case 'geral':
                // Define a taxa de comissão com base na meta de faturamento mensal
                $taxaComissao = ($faturamentoBrutoMensal >= self::META_FATURAMENTO_GERAL)
                                ? self::COMISSAO_GERAL_BONUS
                                : self::COMISSAO_GERAL_BASE;
                $comissaoDentista = $valorBruto * $taxaComissao;
                break;
            case 'especializado':
                $comissaoDentista = $valorBruto * self::COMISSAO_ESPECIALIZADO;
                break;
            case 'protese':
                $custoProteseLab = floatval($custoProteticoManual);
                $comissaoDentista = $valorBruto * self::COMISSAO_PROTESE_DENTISTA;
                break;
        }

        return [
            'dentista' => $comissaoDentista,
            'protetico' => $custoProteseLab
        ];
    }
}
?>