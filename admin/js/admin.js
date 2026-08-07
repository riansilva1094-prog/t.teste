// ===== MASCARAS DE INPUT =====
function mascararTelefone(valor) {
    valor = valor.replace(/\D/g, '').slice(0, 11);
    if (valor.length === 0) return '';
    if (valor.length <= 2) return '(' + valor;
    if (valor.length <= 6) return '(' + valor.slice(0, 2) + ') ' + valor.slice(2);
    if (valor.length <= 10) return '(' + valor.slice(0, 2) + ') ' + valor.slice(2, 6) + '-' + valor.slice(6);
    return '(' + valor.slice(0, 2) + ') ' + valor.slice(2, 7) + '-' + valor.slice(7);
}

function mascararSomenteNumeros(valor, tamanhoMaximo) {
    valor = valor.replace(/\D/g, '');
    return tamanhoMaximo ? valor.slice(0, tamanhoMaximo) : valor;
}

document.querySelectorAll('input[data-mask="telefone"]').forEach((input) => {
    input.addEventListener('input', () => {
        input.value = mascararTelefone(input.value);
    });
});

document.querySelectorAll('input[data-mask="numeros"]').forEach((input) => {
    const tamanhoMaximo = input.dataset.maskLength ? parseInt(input.dataset.maskLength, 10) : null;
    input.addEventListener('input', () => {
        input.value = mascararSomenteNumeros(input.value, tamanhoMaximo);
    });
});

document.querySelectorAll('input[data-mask="maiusculas"]').forEach((input) => {
    input.addEventListener('input', () => {
        const posicao = input.selectionStart;
        input.value = input.value.toUpperCase();
        input.setSelectionRange(posicao, posicao);
    });
});
