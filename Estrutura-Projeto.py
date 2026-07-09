import os

Pasta_Projeto = "Projeto_v1.0.6"

# Definir estrutura de Pastas e Arquivos
Estrutura = {
    "Assets/css" : ["style.css"],
    "Layouts" : ["Header.php", "Footer.php"],
    "Models" : ["Sakila-Model.php"],
    "" : [".env", ".gitignore", "database.php", "Crud.php", "Lixeira.php", "Start-Tables.php"]
}

os.makedirs(Pasta_Projeto, exist_ok=True)

for Pasta, Arquivos in Estrutura.items():
    if Pasta:
        Caminho_Pasta = os.path.join(Pasta_Projeto, Pasta)
        os.makedirs(Caminho_Pasta, exist_ok=True)
        Alvo = Caminho_Pasta
    else:
        Alvo = Pasta_Projeto
        
    for Arquivo in Arquivos:
        Caminho_Completo = os.path.join(Alvo, Arquivo)
        if not os.path.exists(Caminho_Completo):
            with open(Caminho_Completo, "w", encoding="utf=8") as f:
                pass
            
print(f"Sucesso! Tudo foi criado rigorosamente dentro de '{Pasta_Projeto}'.")