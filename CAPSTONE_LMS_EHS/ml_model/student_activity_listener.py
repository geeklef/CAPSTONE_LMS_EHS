import psycopg2
import select
from predict_student import predict_and_upsert  # Import your prediction function

# --- Database connection ---
conn = psycopg2.connect(
    dbname="postgres",           # your Supabase DB name
    user="postgres.fgsohkazfoskhxhndogu",  # Supabase user
    password="lms_ehs_123",      # Supabase password
    host="aws-1-ap-southeast-1.pooler.supabase.com",
    port="5432"
)

conn.set_isolation_level(psycopg2.extensions.ISOLATION_LEVEL_AUTOCOMMIT)
cur = conn.cursor()

# --- Listen to the notification channel ---
cur.execute("LISTEN student_activity;")
print("🚀 Listening for student activity notifications...")

while True:
    # Wait for notifications (timeout 5 sec)
    if select.select([conn], [], [], 5) == ([], [], []):
        continue

    conn.poll()  # Check for new notifications
    while conn.notifies:
        notify = conn.notifies.pop(0)
        try:
            stud_id_str, class_id_str = notify.payload.split(',')
            stud_id = int(stud_id_str)
            class_id = int(class_id_str)

            print(f"🔁 Detected activity for Student {stud_id}, Class {class_id}")
            predict_and_upsert(stud_id, class_id)

        except Exception as e:
            print(f"⚠ Error processing notification: {notify.payload} | {e}")
