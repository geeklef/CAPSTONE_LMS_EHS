import pandas as pd
import joblib
import random
import json

# -------------------------
# Load Trained Model
# -------------------------
model_path = "C:\\xampp\\htdocs\\CAPSTONE_LMS_EHS\\ML_MODEL\\pass_fail_model_xgb.pkl"
model = joblib.load(model_path)

# -------------------------
# Motivational Quotes (for failing students)
# -------------------------
quotes = [
    "Don’t be discouraged — even failure is a step closer to success.",
    "Keep pushing forward — small progress each day adds up to big results.",
    "Believe in yourself — every expert was once a beginner.",
    "Mistakes are proof that you’re trying. Keep learning!",
    "Failure is not the end, it’s a lesson for your comeback.",
    "You’re stronger than your struggles. Don’t give up!",
    "It’s okay to fall — what matters is that you rise again.",
    "Difficult roads often lead to beautiful destinations.",
    "Every champion was once a student who refused to quit.",
    "Stay positive, work hard, and make it happen."
]

# -------------------------
# Function to Analyze Weakness
# -------------------------
def analyze_weakness(data):
    issues = []

    if data['avg_quiz_score'] < 75:
        issues.append("Low quiz scores")
    if data['avg_activity_score'] < 75:
        issues.append("Low activity performance")
    if data['absent_rate'] > 25:
        issues.append("High absence rate")
    if data['behavior_count'] < 1:
        issues.append("Low participation or behavior score")
    if data['late_submission_count'] > 3:
        issues.append("Too many late submissions")

    if not issues:
        return "Overall good performance, but consistency is needed."
    else:
        return ", ".join(issues)

# -------------------------
# Main Prediction Function
# -------------------------
def predict_student_performance(avg_activity_score, avg_quiz_score, absent_rate, behavior_count, late_submission_count):
    # Derived features
    engagement_score = avg_activity_score + avg_quiz_score
    penalty_score = late_submission_count * absent_rate
    adjusted_behavior = behavior_count / (1 + late_submission_count)
    behavioral_score = behavior_count * 2 - late_submission_count

    # Create DataFrame
    df = pd.DataFrame([{
        "avg_activity_score": avg_activity_score,
        "avg_quiz_score": avg_quiz_score,
        "absent_rate": absent_rate,
        "behavior_count": behavior_count,
        "engagement_score": engagement_score,
        "penalty_score": penalty_score,
        "adjusted_behavior": adjusted_behavior,
        "behavioral_score": behavioral_score
    }])

    # Predict
    prediction = model.predict(df)[0]

    # Prepare Result
    if prediction == 1:
        result = {
            "status": "pass",
            "message": "Student is performing well.",
            "weakness": None,
            "quote": random.choice([
                "Great job! Keep maintaining your strong performance.",
                "Consistency is key — keep it up!",
                "Your effort is paying off, continue doing great!"
            ])
        }
    else:
        result = {
            "status": "fail",
            "message": "Student is at risk of failing.",
            "weakness": analyze_weakness({
                "avg_activity_score": avg_activity_score,
                "avg_quiz_score": avg_quiz_score,
                "absent_rate": absent_rate,
                "behavior_count": behavior_count,
                "late_submission_count": late_submission_count
            }),
            "quote": random.choice(quotes)
        }

    return result

# -------------------------
# Run Example (Test)
# -------------------------
if __name__ == "__main__":
    result = predict_student_performance(
        avg_activity_score=68,
        avg_quiz_score=60,
        absent_rate=40,
        behavior_count=1,
        late_submission_count=4
    )

    # Print formatted result
    print("📊 Prediction Result:")
    print(f"Status   : {result['status'].upper()}")
    print(f"Message  : {result['message']}")
    print(f"Weakness : {result['weakness']}")
    print(f"Quote    : {result['quote']}")
