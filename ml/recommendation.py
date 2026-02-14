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
        
        # Check if enough data
        if df.shape[0] < 5 or 'user_id' not in df.columns or 'product_id' not in df.columns:
            print("Not enough data for ML. Recommending popular items.")
            # Simple logic: Top items by count
            top_items = df['product_id'].value_counts().head(5).index.tolist()
            recommendations = {str(uid): top_items for uid in df['user_id'].unique()}
        else:
            # Collaborative Filtering using KNN
            # Create User-Item Matrix
            # If duplicates (user bought same item multiple times), sum up the rating/count
            if 'rating' not in df.columns:
                df['rating'] = 1
                
            matrix = df.pivot_table(index='user_id', columns='product_id', values='rating', aggfunc='sum').fillna(0)
            
            # Simple KNN model
            print("Training model...")
            model = NearestNeighbors(metric='cosine', algorithm='brute')
            model.fit(matrix)
            
            recommendations = {}
            user_ids = matrix.index.tolist()
            
            for i, user_id in enumerate(user_ids):
                # Find neighbors
                distances, indices = model.kneighbors(matrix.iloc[i].values.reshape(1, -1), n_neighbors=min(6, len(user_ids)))
                
                # Get neighbor indices (excluding self)
                neighbor_indices = [idx for idx in indices.flatten() if idx != i]
                
                # Collect candidate items from neighbors
                candidates = {}
                user_interacted = set(matrix.iloc[i][matrix.iloc[i] > 0].index)
                
                for n_idx in neighbor_indices:
                    neighbor_user_id = user_ids[n_idx]
                    neighbor_items = matrix.iloc[n_idx]
                    # Items neighbor liked
                    for item_id, rating in neighbor_items[neighbor_items > 0].items():
                        if item_id not in user_interacted:
                            candidates[item_id] = candidates.get(item_id, 0) + (rating * (1 / (distances.flatten()[list(indices.flatten()).index(n_idx)] + 0.001)))

                # Sort candidates by score
                sorted_candidates = sorted(candidates.items(), key=lambda x: x[1], reverse=True)[:5]
                recommendations[str(user_id)] = [item[0] for item in sorted_candidates]
            
            # For users not in matrix (if logic allows partial updates), handled by Symfony
            
        # Save output
        print(f"Saving recommendations to {output_file}...")
        
        with open(output_file, 'w') as f:
            # Ensure keys are strings because JSON keys must be strings
            json.dump(recommendations, f)
            
    except Exception as e:
        print(f"Error: {e}")
        # Write empty object to avoid breaking PHP
        with open(output_file, 'w') as f:
            json.dump({}, f)

if __name__ == "__main__":
    if len(sys.argv) < 3:
        print("Usage: python recommendation.py <input_json> <output_json>")
        sys.exit(1)
        
    input_path = sys.argv[1]
    output_path = sys.argv[2]
    
    recommend(input_path, output_path)
