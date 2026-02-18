import json
import pandas as pd
import numpy as np
import sys
import os
from sklearn.neighbors import NearestNeighbors

def recommend(input_file, output_file):
    try:
        # Load interactions
        print(f"Loading data from {input_file}...")
        with open(input_file, 'r') as f:
            data = json.load(f)
        
        if not data:
            print("No data found. Exiting.")
            with open(output_file, 'w') as f:
                json.dump({}, f)
            return

        df = pd.DataFrame(data)
        
        # 1. BASKET POPULARITY CALCULATION
        # Sum of ratings per product - This is the "Basket Score" (increases with every quantity added)
        popularity_counts = df.groupby('product_id')['rating'].sum()
        
        # Normalize between 0 and 1
        if not popularity_counts.empty and popularity_counts.max() != popularity_counts.min():
            basket_scores = (popularity_counts - popularity_counts.min()) / (popularity_counts.max() - popularity_counts.min())
        else:
            basket_scores = popularity_counts / (popularity_counts.max() if not popularity_counts.empty and popularity_counts.max() > 0 else 1)
            
        # Check if enough data for ML
        if df.shape[0] < 5 or 'user_id' not in df.columns or 'product_id' not in df.columns:
            print("Not enough data for ML. Recommending popular items based on basket counts.")
            top_items = popularity_counts.sort_values(ascending=False).head(10).index.tolist()
            recommendations = {str(uid): top_items[:5] for uid in df['user_id'].unique()}
        else:
            # Collaborative Filtering using KNN
            if 'rating' not in df.columns:
                df['rating'] = 1
                
            matrix = df.pivot_table(index='user_id', columns='product_id', values='rating', aggfunc='sum').fillna(0)
            
            # model
            print("Training model...")
            model = NearestNeighbors(metric='cosine', algorithm='brute')
            model.fit(matrix)
            
            recommendations = {}
            user_ids = matrix.index.tolist()
            
            # WEIGHT: How much global popularity (basket count) boosts the score
            BASKET_POPULARITY_WEIGHT = 1.5 
            
            # Top 20 most popular products globally (discovery component)
            top_global_items = popularity_counts.sort_values(ascending=False).head(20).index.tolist()
            
            for i, user_id in enumerate(user_ids):
                # Neighbors
                n_neighbors = min(10, len(user_ids))
                distances, indices = model.kneighbors(matrix.iloc[i].values.reshape(1, -1), n_neighbors=n_neighbors)
                
                neighbor_indices = [idx for idx in indices.flatten() if idx != i]
                
                candidates = {}
                user_interacted = set(matrix.iloc[i][matrix.iloc[i] > 0].index)
                
                # A. Similarity-based candidates
                for n_idx in neighbor_indices:
                    neighbor_items = matrix.iloc[n_idx]
                    dist = distances.flatten()[list(indices.flatten()).index(n_idx)]
                    similarity = 1 / (dist + 0.001)
                    
                    for item_id, rating in neighbor_items[neighbor_items > 0].items():
                        if item_id not in user_interacted:
                            ml_score = rating * similarity
                            candidates[item_id] = candidates.get(item_id, 0) + ml_score

                # B. Global Popularity injection (ensure 'hot' items are candidates)
                for item_id in top_global_items:
                    if item_id not in user_interacted:
                        if item_id not in candidates:
                            candidates[item_id] = 0 # Add to pool with 0 base score

                # C. Final Scoring: Add the "Panier Score" boost
                for item_id in candidates:
                    p_score = basket_scores.get(item_id, 0)
                    # Score = Personalized Match + (Global Basket Popularity * Weight)
                    candidates[item_id] = candidates[item_id] + (p_score * BASKET_POPULARITY_WEIGHT * 10)

                # Sort and pick top 5
                sorted_candidates = sorted(candidates.items(), key=lambda x: x[1], reverse=True)[:5]
                recommendations[str(user_id)] = [int(item[0]) for item in sorted_candidates]
            
        # Save output
        print(f"Saving recommendations to {output_file}...")
        with open(output_file, 'w') as f:
            json.dump(recommendations, f)
            
    except Exception as e:
        print(f"Error: {e}")
        import traceback
        traceback.print_exc()
        with open(output_file, 'w') as f:
            json.dump({}, f)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python recommendation.py <input_json> <output_json>")
        sys.exit(1)
        
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    recommend(input_path, output_path)
