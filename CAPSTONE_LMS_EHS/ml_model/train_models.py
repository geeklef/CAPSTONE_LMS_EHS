import os
import pandas as pd
from xgboost import XGBRegressor, XGBClassifier
import joblib

# --- Get current script folder ---
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
EXCEL_PATH = os.path.join(BASE_DIR, "student_performance.xlsx")

# --- Load Excel data ---
df = pd.read_excel(EXCEL_PATH)

# --- Features and target ---
features = ["avg_exam_score","avg_quiz_score","avg_activity_score","absent_rate","late_submission_count","behavior_score"]

X = df[features]

# --- Grade model (regression) ---
y_grade = df["final_grade"]
grade_model = XGBRegressor(
    n_estimators=100,
    max_depth=4,
    learning_rate=0.1,
    random_state=42
)
grade_model.fit(X, y_grade)
joblib.dump(grade_model, os.path.join(BASE_DIR, "grade_predictor_xgb.pkl"))
print("✅ grade_predictor_xgb.pkl saved!")

# --- Pass/Fail model (classification) ---
y_passfail = df["will_pass"]
pass_model = XGBClassifier(
    n_estimators=100,
    max_depth=4,
    learning_rate=0.1,
    random_state=42
)
pass_model.fit(X, y_passfail)
joblib.dump(pass_model, os.path.join(BASE_DIR, "pass_fail_model_xgb.pkl"))
print("✅ pass_fail_model_xgb.pkl saved!")
