let carrinho = JSON.parse(localStorage.getItem('cart')) || [];

function adicionarAoCarrinho(nome, preco) {
    // Procura se já existe o produto
    let itemExistente = carrinho.find(i => i.nome === nome);
    if (itemExistente) {
        itemExistente.qtd += 1;
    } else {
        carrinho.push({ nome, preco, qtd: 1, selecionado: true });
    }
    localStorage.setItem('cart', JSON.stringify(carrinho));
    alert(nome + " adicionado!");
}

function atualizarResumo() {
    const lista = document.getElementById('lista-itens');
    const totalSpan = document.getElementById('total-carrinho');
    if(!lista) return;
    lista.innerHTML = '';
    let total = 0;
    carrinho.forEach((item, index) => {
        lista.innerHTML += `
            <li class="flex items-center gap-2 py-2 border-b">
                <input type="checkbox" checked onchange="toggleItem(${index})">
                <span>${item.nome} - R$ ${item.preco.toFixed(2)}</span>
            </li>`;
        if(item.selecionado) total += item.preco;
    });
    totalSpan.innerText = total.toFixed(2);
}

function toggleItem(index) {
    carrinho[index].selecionado = !carrinho[index].selecionado;
    atualizarResumo();
}

function toggleModal() {
    document.getElementById('modal-carrinho').classList.toggle('hidden');
}

function enviarPedido() {
    let msg = "Pedido:\n";
    carrinho.filter(i => i.selecionado).forEach(i => msg += `- ${i.nome}: R$ ${i.preco.toFixed(2)}\n`);
    // Pegar o telefone do elemento ou da variável global do PHP
    let tel = "<?= $empresa_whatsapp ?>"; 
    window.open(`https://wa.me/${tel}?text=${encodeURIComponent(msg)}`, '_blank');
}