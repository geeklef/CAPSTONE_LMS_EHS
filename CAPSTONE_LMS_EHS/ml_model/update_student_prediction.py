import pandas as pd
import joblib
import random
from datetime import datetime
from supabase import create_client

# -------------------------
# Supabase config
# -------------------------
SUPABASE_URL = "https://fgsohkazfoskhxhndogu.supabase.co"
SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MDQzNTgwMiwiZXhwIjoyMDc2MDExODAyfQ.3-WklcMwoYIS3VA2Gf5DAy-Rb1ttHvL_ebLrjnn3zkw"
supabase = create_client(SUPABASE_URL, SUPABASE_KEY)

# -------------------------
# Load ML Models
# -------------------------
pass_model = joblib.load("pass_fail_model_xgb.pkl")
grade_model = joblib.load("grade_predictor_xgb.pkl")  # numeric grade regressor

quotes = [
    "Don’t be discouraged — even failure is a step closer to success.",
    "Keep pushing forward — small progress each day adds up to big results.",
    "Believe in yourself — every expert was once a beginner.",
    "Mistakes are proof that you’re trying. Keep learning!",
]

# -------------------------
# Feature computation
# -------------------------
def get_student_features(stud_id):
    # Activities
    activities = supabase.table("stud_activity_submissions").select("score").eq("stud_id", stud_id).execute().data
    avg_activity_score = pd.DataFrame(activities)['score'].mean() if activities else 0

    # Quizzes
    quizzes = supabase.table("stud_quiz_results").select("score").eq("stud_id", stud_id).execute().data
    avg_quiz_score = pd.DataFrame(quizzes)['score'].mean() if quizzes else 0

    # Exams
    exams = supabase.table("stud_exam_results").select("score").eq("stud_id", stud_id).execute().data
    avg_exam_score = pd.DataFrame(exams)['score'].mean() if exams else 0

    # Attendance
    attendance = supabase.table("stud_attendance").select("status").eq("student_id", stud_id).execute().data
    df_att = pd.DataFrame(attendance)
    total_att = len(df_att)
    absent_count = len(df_att[df_att['status']=="Absent"]) if total_att else 0
    late_count = len(df_att[df_att['status']=="Late"]) if total_att else 0
    absent_rate = absent_count / total_att if total_att else 0
    late_submission_count = late_count

    # Module time (behavior)
    modules = supabase.table("stud_module_time").select("duration_seconds").eq("stud_id", stud_id).execute().data
    behavior_score = pd.DataFrame(modules)['duration_seconds'].sum()/60 if modules else 0

    return {
        "avg_activity_score": avg_activity_score,
        "avg_quiz_score": avg_quiz_score,
        "avg_exam_score": avg_exam_score,
        "absent_rate": absent_rate,
        "late_submission_count": late_submission_count,
        "behavior_score": behavior_score
    }

# -------------------------
# Predict & upsert
# -------------------------
def predict_and_upsert(stud_id, class_id):
    features = get_student_features(stud_id)
    df_features = pd.DataFrame([features])

    # ML predictions
    pass_fail = pass_model.predict(df_features)[0]
    predicted_grade = float(grade_model.predict(df_features)[0])
    predicted_grade = max(0, min(predicted_grade, 100))

    # Weakness
    issues = []
    if features['avg_quiz_score'] < 75: issues.append("Low quiz scores")
    if features['avg_activity_score'] < 75: issues.append("Low activity performance")
    if features['absent_rate'] > 0.25: issues.append("High absence rate")
    if features['behavior_score'] < 50: issues.append("Low engagement/module time")
    if features['late_submission_count'] > 3: issues.append("Too many late submissions")
    weakness = ", ".join(issues) if issues else None

    # Motivational quote
    quote = random.choice(quotes)

    # Upsert into Supabase
    supabase.table("student_predictions").upsert({
        "stud_id": stud_id,
        "class_id": class_id,
        "predicted_grade": round(predicted_grade,2),
        "pass_fail_status": "pass" if pass_fail==1 else "fail",
        "weakness": weakness,
        "quote": quote,
        "last_updated": datetime.utcnow().isoformat()
    }, on_conflict=["stud_id","class_id"]).execute()


# -------------------------
# Example execution
# -------------------------
if __name__ == "__main__":
    STUD_ID = 2200661   # from PHP session
    CLASS_ID = 1        # fetch from PHP/class enrollment
    predict_and_upsert(STUD_ID, CLASS_ID)
    print("✅ Prediction updated")
