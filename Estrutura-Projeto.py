import os

root = "Projeto_v1.0.6"

# Estrutura: { Pasta: [Arquivos] }
folders = {
    "assets/css": ["style_default.css"],
    "assets/js": ["cart.js", "script_default.js"],
    "assets/imgs": [], # Pasta vazia para imagens
    "config": ["Connection.php", "routes.php"],
    "controllers": ["AuthController.php", "ProductController.php", "CompanyController.php"],
    "models": ["Crud.php", "ProductModel.php", "SakilaModel.php"],
    "views/layouts": ["header.php", "header-public.php", "footer.php"],
    "views/admin": ["cadastrar-empresa.php"],
    "views/company": ["dashboard.php", "lixeira.php"],
    "views/catalog": ["index.php", "carrinho.php"],
    "views/sakila": ["tabelas-sakila.php", "formulario.php"],
    "views": ["login.php"],
    "docs": ["sobre_mim.md", "como_navegar.md", "apresentacao_do_projeto.md", "estrutura_de_pastas.md"],
    "storage/logs": ["acesso.log"]
}

os.makedirs(root, exist_ok=True)

# 1. Criar pastas e arquivos dentro das pastas (com proteção)
for folder, files in folders.items():
    path = os.path.join(root, folder)
    os.makedirs(path, exist_ok=True)
    for file in files:
        file_path = os.path.join(path, file)
        if not os.path.exists(file_path):
            with open(file_path, "w", encoding="utf-8") as f:
                pass

# 2. Criar arquivos na raiz COM VALIDAÇÃO (Não sobrescreve se existir)
root_files = [".env", ".gitignore", ".htaccess", "config.php", "index.php"]
for file in root_files:
    file_path = os.path.join(root, file)
    if not os.path.exists(file_path):
        with open(file_path, "w", encoding="utf-8") as f:
            pass
    else:
        print(f"Aviso: O arquivo '{file}' já existe e foi preservado.")

print(f"Estrutura '{root}' verificada/criada com sucesso!")