import pandas as pd
from xgboost import XGBRegressor
from sklearn.model_selection import train_test_split
from sklearn.metrics import mean_absolute_error, r2_score
import joblib

# Load dataset
file_path = "student_performance.xlsx"
df = pd.read_excel(file_path)

# Features and target   
X = df[['avg_exam_score', 'avg_quiz_score', 'avg_activity_score',
        'absent_rate', 'late_submission_count', 'behavior_score']]
y = df['final_grade']

# Train/test split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

# Train XGBoost Regressor
regressor = XGBRegressor(n_estimators=200, max_depth=5, learning_rate=0.1, random_state=42)
regressor.fit(X_train, y_train)

# Evaluate
y_pred = regressor.predict(X_test)
mae = mean_absolute_error(y_test, y_pred)
r2 = r2_score(y_test, y_pred)
print(f"✅ Regressor MAE: {mae:.2f}, R2: {r2:.2f}")

# Save model
joblib.dump(regressor, "grade_predictor_xgb.pkl")
print("📁 Grade predictor model saved as grade_predictor_xgb.pkl")
