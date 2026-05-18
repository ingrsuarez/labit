/**
 * Evaluador de fórmulas de determinaciones (espejo de TestFormulaEvaluator PHP).
 */
(function (global) {
    function parseNumeric(value) {
        if (value === null || value === undefined) return null;
        const trimmed = String(value).trim();
        if (trimmed === '') return null;
        const normalized = trimmed.replace(',', '.');
        if (normalized === '' || Number.isNaN(Number(normalized))) return null;
        return Number(normalized);
    }

    function formatNumber(value, decimals) {
        const d = Math.max(0, Math.min(6, decimals ?? 2));
        return Number(value).toFixed(d);
    }

    function applyOperator(output, operator) {
        if (output.length < 2) throw new Error('Invalid expression');
        const b = output.pop();
        const a = output.pop();
        if (operator === '+') output.push(a + b);
        else if (operator === '-') output.push(a - b);
        else if (operator === '*') output.push(a * b);
        else if (operator === '/') {
            if (b === 0) throw new Error('Division by zero');
            output.push(a / b);
        } else throw new Error('Unknown operator');
    }

    function evaluateNumericExpression(expression) {
        const tokens = expression.trim().split(/\s+/).filter(Boolean);
        if (!tokens.length) return null;

        const output = [];
        const operators = [];
        const precedence = { '+': 1, '-': 1, '*': 2, '/': 2 };

        for (const token of tokens) {
            if (!Number.isNaN(Number(token))) {
                output.push(Number(token));
            } else if (token === '(') {
                operators.push(token);
            } else if (token === ')') {
                while (operators.length && operators[operators.length - 1] !== '(') {
                    applyOperator(output, operators.pop());
                }
                if (!operators.length || operators.pop() !== '(') return null;
            } else if (precedence[token] !== undefined) {
                while (
                    operators.length &&
                    operators[operators.length - 1] !== '(' &&
                    precedence[operators[operators.length - 1]] >= precedence[token]
                ) {
                    applyOperator(output, operators.pop());
                }
                operators.push(token);
            } else {
                return null;
            }
        }

        while (operators.length) {
            const op = operators.pop();
            if (op === '(' || op === ')') return null;
            applyOperator(output, op);
        }

        if (output.length !== 1) return null;
        return output[0];
    }

    function buildNumericExpression(tokens, valuesByTestId) {
        const parts = [];

        for (const token of tokens) {
            if (token.type === 'test') {
                const testId = Number(token.test_id);
                const raw = valuesByTestId[testId] ?? valuesByTestId[String(testId)];
                const numeric = parseNumeric(raw);
                if (numeric === null) return null;
                parts.push(String(numeric));
            } else if (token.type === 'number') {
                const numeric = parseNumeric(token.value);
                if (numeric === null) return null;
                parts.push(String(numeric));
            } else if (token.type === 'op') {
                if (!['+', '-', '*', '/'].includes(token.value)) return null;
                parts.push(token.value);
            } else if (token.type === 'paren') {
                if (!['(', ')'].includes(token.value)) return null;
                parts.push(token.value);
            } else {
                return null;
            }
        }

        return parts.length ? parts.join(' ') : null;
    }

    function evaluate(definition, valuesByTestId, decimals) {
        if (!definition || !definition.tokens || !definition.tokens.length) return null;

        const expression = buildNumericExpression(definition.tokens, valuesByTestId);
        if (expression === null) return null;

        try {
            const result = evaluateNumericExpression(expression);
            if (result === null) return null;
            return formatNumber(result, decimals ?? 2);
        } catch (e) {
            return null;
        }
    }

    function collectValuesFromContainer(container) {
        const values = {};
        container.querySelectorAll('[data-formula-operand]').forEach((input) => {
            const testId = input.getAttribute('data-test-id');
            if (testId) values[testId] = input.value;
        });
        return values;
    }

    function recalculateFormulas(container) {
        if (!container) return;

        const values = collectValuesFromContainer(container);

        container.querySelectorAll('[data-formula-calculated]').forEach((input) => {
            let definition = null;
            try {
                definition = JSON.parse(input.getAttribute('data-formula-definition') || 'null');
            } catch (e) {
                definition = null;
            }

            const decimals = Number(input.getAttribute('data-formula-decimals') || 2);
            const result = evaluate(definition, values, decimals);

            if (result === null) {
                input.value = '';
                input.placeholder = '—';
            } else {
                input.value = result;
                input.placeholder = 'Resultado';
            }
        });
    }

    function bindFormulaRecalculation(container) {
        if (!container || container.dataset.formulaBound === '1') return;
        container.dataset.formulaBound = '1';

        const handler = () => recalculateFormulas(container);
        container.addEventListener('input', (event) => {
            if (event.target.matches('[data-formula-operand]')) {
                handler();
            }
        });
        container.addEventListener('change', (event) => {
            if (event.target.matches('[data-formula-operand]')) {
                handler();
            }
        });

        handler();
    }

    global.LabitTestFormula = {
        parseNumeric,
        formatNumber,
        evaluate,
        recalculateFormulas,
        bindFormulaRecalculation,
    };
})(window);
