<?php include VIEWS_PATH . 'layouts/header-public.php'; ?>

<div class="max-w-3xl mx-auto p-6 bg-white shadow-lg mt-10 rounded-lg">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold">Meu Carrinho</h2>
        <a href="javascript:history.back()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
            <i class="fas fa-arrow-left mr-2"></i> Voltar
        </a>
    </div>
    
    <div id="lista-carrinho" class="space-y-4">
        </div>
    
    <div class="mt-6 border-t pt-4 text-xl font-bold text-right">
        Total: R$ <span id="total-geral">0,00</span>
    </div>
    
    <button onclick="finalizarPedido()" class="w-full mt-6 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700">
        Enviar Pedido via WhatsApp
    </button>
</div>

<script>
function renderizarCarrinho() {
    let carrinho = JSON.parse(localStorage.getItem('cart')) || [];
    let container = document.getElementById('lista-carrinho');
    let total = 0;
    container.innerHTML = '';
    
    if (carrinho.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-500">Seu carrinho está vazio.</p>';
    }

    carrinho.forEach((item, index) => {
        total += (item.preco * item.qtd);
        container.innerHTML += `
            <div class="flex justify-between items-center border-b pb-2">
                <div>
                    <p class="font-bold">${item.nome}</p>
                    <p class="text-sm">Qtd: ${item.qtd} | R$ ${(item.preco * item.qtd).toFixed(2)}</p>
                </div>
                <button onclick="removerItem(${index})" class="text-red-500 hover:text-red-700">Remover</button>
            </div>
        `;
    });
    document.getElementById('total-geral').innerText = total.toFixed(2);
}

function removerItem(index) {
    let carrinho = JSON.parse(localStorage.getItem('cart')) || [];
    carrinho.splice(index, 1);
    localStorage.setItem('cart', JSON.stringify(carrinho));
    renderizarCarrinho();
}

function finalizarPedido() {
    let carrinho = JSON.parse(localStorage.getItem('cart')) || [];
    let msg = "Olá! Gostaria de fazer o seguinte pedido:\n\n";
    carrinho.forEach(i => msg += `- ${i.nome} (Qtd: ${i.qtd}): R$ ${(i.preco * i.qtd).toFixed(2)}\n`);
    
    // O número deve ser passado pela sessão ou variável de template
    let tel = "<?= isset($empresa_whatsapp) ? $empresa_whatsapp : '0000000000' ?>"; 
    window.open(`https://wa.me/${tel}?text=${encodeURIComponent(msg)}`, '_blank');
}

renderizarCarrinho();
</script>

<?php include VIEWS_PATH . 'layouts/footer.php'; ?>