import pandas as pd
from xgboost import XGBClassifier
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, classification_report, confusion_matrix
import joblib

# Load dataset
file_path = "student_performance.xlsx"
df = pd.read_excel(file_path)

# Features and target
X = df[['avg_exam_score', 'avg_quiz_score', 'avg_activity_score',
        'absent_rate', 'late_submission_count', 'behavior_score']]
y = df['will_pass']

# Train/test split
X_train, X_test, y_train, y_test = train_test_split(X, y, test_size=0.3, random_state=42)

# Train XGBoost Classifier
model = XGBClassifier(n_estimators=200, max_depth=5, learning_rate=0.1, random_state=42)
model.fit(X_train, y_train)

# Evaluate
y_pred = model.predict(X_test)
accuracy = accuracy_score(y_test, y_pred)
print(f"✅ Classifier Accuracy: {accuracy:.2f}")
print(classification_report(y_test, y_pred))

# Save model
joblib.dump(model, "pass_fail_model_xgb.pkl")
print("📁 Pass/Fail model saved as pass_fail_model_xgb.pkl")
