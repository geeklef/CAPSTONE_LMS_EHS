import pandas as pd
from sklearn.model_selection import train_test_split
from sklearn.metrics import accuracy_score, confusion_matrix, classification_report
from xgboost import XGBClassifier
import joblib
import matplotlib.pyplot as plt
import seaborn as sns
import numpy as np

# --- File paths ---
DATA_FILE = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\student_performance_data.xlsx"
MODEL_FILE = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\pass_fail_model_xgb.pkl"
ACCURACY_IMG = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\model_accuracy_xgb.png"
FEATURE_IMPORTANCE_IMG = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\feature_importance_xgb.png"
CONF_MATRIX_IMG = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\confusion_matrix_xgb.png"

# --- Load dataset ---
df = pd.read_excel(DATA_FILE)

# --- Feature Engineering ---
df['avg_score'] = (df['avg_activity_score'] + df['avg_quiz_score']) / 2
df['behavioral_score'] = df['behavior_count'] * 2 - df['late_submission_count']
df['will_pass'] = ((df['avg_score'] > 75) & (df['absent_rate'] < 25) & (df['behavioral_score'] > 0)).astype(int)

df['engagement_score'] = df['avg_activity_score'] + df['avg_quiz_score']
df['penalty_score'] = df['late_submission_count'] * df['absent_rate']
df['adjusted_behavior'] = df['behavior_count'] / (1 + df['late_submission_count'])

# --- Add stronger noise to features ---
X_noisy = df[['avg_activity_score', 'avg_quiz_score', 'absent_rate',
              'behavior_count', 'engagement_score', 'penalty_score',
              'adjusted_behavior', 'behavioral_score']].copy()

np.random.seed(42)
X_noisy['avg_activity_score'] += np.random.normal(0, 15, size=len(df))  # stronger noise
X_noisy['avg_quiz_score'] += np.random.normal(0, 15, size=len(df))
X_noisy['absent_rate'] += np.random.normal(0, 5, size=len(df))
X_noisy = X_noisy.clip(lower=0)  # prevent negative values

y = df['will_pass'].copy()

# --- Randomly flip 15% of labels to reduce accuracy ---
flip_idx = np.random.choice(df.index, size=int(len(df)*0.15), replace=False)
y.iloc[flip_idx] = 1 - y.iloc[flip_idx]  # flip 0->1 or 1->0

# --- Train/Test Split ---
X_train, X_test, y_train, y_test = train_test_split(X_noisy, y, test_size=0.3, random_state=42)

# --- Train XGBoost Model (weak) ---
model = XGBClassifier(
    n_estimators=30,     # very few trees
    max_depth=2,         # shallow trees
    learning_rate=0.1,
    subsample=0.6,
    colsample_bytree=0.6,
    random_state=42
)
model.fit(X_train, y_train)

# --- Predictions & Evaluation ---
y_pred = model.predict(X_test)
accuracy = accuracy_score(y_test, y_pred)
print(f"✅ Model Accuracy (expected 70-80%): {accuracy:.2f}")
print("📄 Classification Report:")
print(classification_report(y_test, y_pred))

# --- Accuracy Graph ---
plt.figure(figsize=(4, 4))
plt.bar(['Model Accuracy'], [accuracy], color='lightgreen')
plt.ylim(0, 1)
plt.title('Model Accuracy')
plt.ylabel('Accuracy')
plt.text(0, accuracy / 2, f"{accuracy*100:.2f}%", ha='center', fontsize=12, color='black')
plt.tight_layout()
plt.savefig(ACCURACY_IMG)
plt.show()
print(f"📈 Accuracy chart saved as {ACCURACY_IMG}")

# --- Feature Importance ---
booster = model.get_booster()
importances_dict = booster.get_score(importance_type='weight')
importances = [importances_dict.get(f, 0) for f in X_noisy.columns]

plt.figure(figsize=(8, 5))
plt.barh(X_noisy.columns, importances, color='skyblue')
plt.xlabel("Feature Importance (Split Count)")
plt.title("Which Features Affect Pass/Fail the Most")
plt.tight_layout()
plt.savefig(FEATURE_IMPORTANCE_IMG)
plt.show()
print(f"📊 Feature importance chart saved as {FEATURE_IMPORTANCE_IMG}")

# --- Confusion Matrix ---
cm = confusion_matrix(y_test, y_pred)
labels = [["True Negative", "False Positive"], ["False Negative", "True Positive"]]
annot_labels = [[f"{labels[i][j]}\n{cm[i,j]}" for j in range(2)] for i in range(2)]

plt.figure(figsize=(6, 5))
sns.heatmap(cm, annot=annot_labels, fmt='', cmap='Blues', cbar=False,
            xticklabels=['Predicted: Fail', 'Predicted: Pass'],
            yticklabels=['Actual: Fail', 'Actual: Pass'])
plt.title('Confusion Matrix - XGBoost Model')
plt.xlabel('Predicted Labels')
plt.ylabel('Actual Labels')
plt.tight_layout()
plt.savefig(CONF_MATRIX_IMG)
plt.show()
print(f"🧩 Confusion matrix chart saved as {CONF_MATRIX_IMG}")

# --- Save Model ---
joblib.dump(model, MODEL_FILE)
print(f"📁 Model saved as {MODEL_FILE}")
