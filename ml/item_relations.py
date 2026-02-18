import json
import pandas as pd
import numpy as np
import sys
import os
from sklearn.metrics.pairwise import cosine_similarity

def calculate_item_relations(input_file, target_product_id, output_file):
    try:
        # 1. Load data
        if not os.path.exists(input_file):
            print(f"Error: {input_file} not found")
            return

        with open(input_file, 'r') as f:
            data = json.load(f)
        
        if not data:
            print("No data found.")
            return

        df = pd.DataFrame(data)
        
        # Ensure we have the necessary columns
        if 'product_id' not in df.columns or 'user_id' not in df.columns:
            print("Invalid input format.")
            return

        # 2. Create Item-User Matrix
        # We want to see which products are bought together by the same users
        # rating is used to weight the interaction
        if 'rating' not in df.columns:
            df['rating'] = 1
            
        matrix = df.pivot_table(index='product_id', columns='user_id', values='rating').fillna(0)
        
        # 3. Calculate Cosine Similarity
        if target_product_id not in matrix.index:
            # If product has no interactions yet, we return empty or global popular items
            # But let's return [] to indicate no specific relations
            with open(output_file, 'w') as f:
                json.dump([], f)
            return

        # Calculate similarity for the target product against all others
        target_vec = matrix.loc[target_product_id].values.reshape(1, -1)
        sim_scores = cosine_similarity(target_vec, matrix.values).flatten()
        
        # Create a series of results
        item_sim = pd.Series(sim_scores, index=matrix.index)
        
        # Remove the target product itself and sort
        related = item_sim.drop(target_product_id).sort_values(ascending=False)
        
        # Filter out products with 0 similarity
        related = related[related > 0]
        
        # Get top 5 IDs
        top_related_ids = [int(p_id) for p_id in related.head(5).index.tolist()]
        
        # 4. Save results
        with open(output_file, 'w') as f:
            json.dump(top_related_ids, f)
        
        print(f"Computed {len(top_related_ids)} related products for {target_product_id}")

    except Exception as e:
        print(f"Error in Python script: {e}")
        with open(output_file, 'w') as f:
            json.dump([], f)

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python item_relations.py <input_json> <target_product_id> <output_json>")
        sys.exit(1)
        
    calculate_item_relations(sys.argv[1], int(sys.argv[2]), sys.argv[3])
