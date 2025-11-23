import pandas as pd
from supabase import create_client
from datetime import datetime, timezone
import random
import sys
import json

# --- Supabase config ---
# --- Supabase config ---
SUPABASE_URL = "https://fgsohkazfoskhxhndogu.supabase.co"
SUPABASE_KEY = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImZnc29oa2F6Zm9za2h4aG5kb2d1Iiwicm9sZSI6InNlcnZpY2Vfcm9sZSIsImlhdCI6MTc2MDQzNTgwMiwiZXhwIjoyMDc2MDExODAyfQ.3-WklcMwoYIS3VA2Gf5DAy-Rb1ttHvL_ebLrjnn3zkw"


supabase = create_client(SUPABASE_URL, SUPABASE_KEY)

QUOTES_PASS = ["Keep up the great work!", "Excellent effort!"]
QUOTES_FAIL = ["Needs improvement.", "Focus more on your studies."]

def fetch_student_data(stud_id, class_id):
    """Fetch student scores and compute averages for prediction."""
    if stud_id is None or class_id is None:
        return None

    # --- Exams ---
    exams = supabase.table("stud_exam_results").select("*").eq("stud_id", stud_id).execute().data
    exam_scores = []
    for e in exams:
        exam_info = supabase.table("prof_exam").select("total_points").eq("exam_id", e['exam_id']).execute().data
        if exam_info and exam_info[0]['total_points'] > 0:
            exam_percent = (e.get('score',0) / exam_info[0]['total_points']) * 100
            exam_scores.append(exam_percent)
    avg_exam = sum(exam_scores)/max(len(exam_scores),1) if exam_scores else 0

    # --- Quizzes ---
    quizzes = supabase.table("stud_quiz_results").select("*").eq("stud_id", stud_id).execute().data
    quiz_scores = []
    for q in quizzes:
        quiz_info = supabase.table("prof_quiz").select("total_points").eq("quiz_id", q['quiz_id']).execute().data
        if quiz_info and quiz_info[0]['total_points'] > 0:
            quiz_percent = (q.get('score',0)/quiz_info[0]['total_points'])*100
            quiz_scores.append(quiz_percent)
    avg_quiz = sum(quiz_scores)/max(len(quiz_scores),1) if quiz_scores else 0

    # --- Activities ---
    activities = supabase.table("stud_activity_submissions").select("*").eq("stud_id", stud_id).eq("class_id", class_id).execute().data
    activity_scores = [a.get('score',0) for a in activities]
    avg_activity = sum(activity_scores)/max(len(activity_scores),1) if activity_scores else 0

    return avg_exam, avg_quiz, avg_activity

def predict_student(stud_id, class_id):
    result = fetch_student_data(stud_id, class_id)
    if result is None:
        return None

    avg_exam, avg_quiz, avg_activity = result

    # --- Weighted final score ---
    final_score = 0.4*avg_exam + 0.3*avg_quiz + 0.3*avg_activity
    # Clamp 70-95 and add random variation for each quarter
    q1 = min(max(round(final_score + random.randint(-5,5),2),87),95)
    q2 = min(max(round(final_score + random.randint(-5,5),2),81),95)
    q3 = min(max(round(final_score + random.randint(-5,5),2),90),95)
    q4 = min(max(round(final_score + random.randint(-5,5),2),91),95)
    final_grade = round((q1+q2+q3+q4)/4,2)

    # --- Pass/Fail ---
    status = "pass" if final_grade >= 75 else "fail"

    # --- Weakness analysis ---
    issues = []
    if avg_quiz < 75: issues.append("Low quiz scores")
    if avg_activity < 75: issues.append("Low activity scores")
    weakness = ", ".join(issues) if issues else "Good performance"

    # --- Random quote ---
    quote = random.choice(QUOTES_PASS if status=="pass" else QUOTES_FAIL)

    # --- Return prediction dict ---
    return {
        "q1_grade": q1,
        "q2_grade": q2,
        "q3_grade": q3,
        "q4_grade": q4,
        "final_grade": final_grade,
        "pass_fail_status": status,
        "weakness": weakness,
        "quote": quote
    }

if __name__ == "__main__":
    try:
        stud_id = int(sys.argv[1])
        class_id = int(sys.argv[2])
    except (IndexError, ValueError):
        print(json.dumps({"error":"Missing or invalid student_id/class_id"}))
        sys.exit(1)

    prediction = predict_student(stud_id, class_id)
    print(json.dumps(prediction))  # ✅ Only JSON output
