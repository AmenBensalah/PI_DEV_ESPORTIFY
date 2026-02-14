import pandas as pd
import numpy as np
import random
import os
from sklearn.metrics.pairwise import cosine_similarity

def generate_synthetic_data(filename='ml/data.csv', num_rows=2500):
    """
    Génère un dataset de test avec des relations entre utilisateurs et produits.
    Structure: user_id, product_id, rating
    """
    print(f"--- Génération de {num_rows} lignes de données ---")
    
    # On simule 100 utilisateurs et 50 produits
    users = [f"User_{i}" for i in range(1, 101)]
    products = [f"Prod_{i}" for i in range(1, 51)]
    
    data = []
    for _ in range(num_rows):
        user = random.choice(users)
        # On crée des "clusters" de goûts pour avoir de vraies relations
        # Si user est dans les 20 premiers, il aime les produits 1-10
        user_idx = int(user.split('_')[1])
        if user_idx <= 20:
            prod = random.choice(products[0:15])
        elif user_idx <= 50:
            prod = random.choice(products[15:35])
        else:
            prod = random.choice(products[30:50])
            
        rating = random.randint(1, 5)
        data.append([user, prod, rating])
    
    df = pd.DataFrame(data, columns=['user_id', 'product_id', 'rating'])
    
    # On s'assure que le dossier existe
    os.makedirs(os.path.dirname(filename), exist_ok=True)
    df.to_csv(filename, index=False)
    print(f"✅ Fichier '{filename}' créé avec succès !")
    return df

def train_item_similarity_model(csv_file):
    """
    Crée un modèle de similarité entre produits basé sur le comportement d'achat.
    """
    print("--- Entraînement du modèle de relation produits ---")
    df = pd.read_csv(csv_file)
    
    # Créer une matrice Produit-Utilisateur
    # On veut voir quels utilisateurs ont acheté quels produits
    item_user_matrix = df.pivot_table(index='product_id', columns='user_id', values='rating').fillna(0)
    
    # Calculer la similarité cosinus entre les produits
    # (Si deux produits sont achetés par les mêmes personnes, ils sont "reliés")
    similarity_matrix = cosine_similarity(item_user_matrix)
    
    # Transformer en DataFrame pour faciliter la recherche
    item_sim_df = pd.DataFrame(similarity_matrix, index=item_user_matrix.index, columns=item_user_matrix.index)
    
    return item_sim_df

def get_recommendations(product_id, similarity_df, n=5):
    """
    Retourne les N produits les plus reliés au produit choisi.
    """
    if product_id not in similarity_df.index:
        return f"❌ Produit '{product_id}' inconnu."
    
    # Récupérer les scores de similarité pour ce produit
    sim_scores = similarity_df[product_id].sort_values(ascending=False)
    
    # Retirer le produit lui-même (la similarité avec soi-même est toujours 1.0)
    sim_scores = sim_scores.drop(product_id)
    
    return sim_scores.head(n)

# --- EXECUTION ---
import sys
import json

if __name__ == "__main__":
    # 1. On s'assure que les données existent (ou on les génère si besoin de démo)
    csv_path = 'ml/data.csv'
    if not os.path.exists(csv_path):
        generate_synthetic_data(csv_path, num_rows=2500)
    
    # 2. Entraîner le modèle
    model = train_item_similarity_model(csv_path)
    
    # 3. Récupérer l'ID produit depuis les arguments (appel par Symfony)
    if len(sys.argv) > 1:
        target_prod = sys.argv[1]
        results = get_recommendations(target_prod, model)
        
        if isinstance(results, str):
            print(json.dumps({"error": results}))
        else:
            # On retourne juste les IDs des produits reliés
            print(json.dumps(results.index.tolist()))
    else:
        # Mode démo si lancé à la main
        test_prod = "Prod_5"
        print(f"\n💡 Produits reliés à '{test_prod}' :")
        recs = get_recommendations(test_prod, model)
        print(recs)
