"""Chạy UI chat với database fixture biệt lập, dọn đúng các process đã mở."""
import os
from pathlib import Path
import re
import subprocess
import sys
import tempfile
import time

database = sys.argv[1]
if not re.fullmatch(r"kiem_tra_chat_ui_[a-f0-9]{16}", database):
    raise ValueError("Sai database fixture")
root = Path(__file__).resolve().parents[1]
env = dict(os.environ, FILESYSTEM_LOCAL_ROOT=str(root / "BE/storage/framework/testing/chat-ui" / database), DB_DATABASE=database, DB_URL="", FRONTEND_URL="http://localhost:5280",
           SANCTUM_STATEFUL_DOMAINS="localhost:5280", SESSION_COOKIE="fitness_chat_ui",
           BROADCAST_CONNECTION="reverb", REVERB_APP_ID="chat-qa", REVERB_APP_KEY="chat-qa-key",
           REVERB_APP_SECRET="chat-qa-secret", REVERB_HOST="127.0.0.1", REVERB_PORT="8082",
           REVERB_SCHEME="http", REVERB_ALLOWED_ORIGINS="localhost",
           VITE_API_BASE_URL="http://localhost:8011/api/v1", VITE_REVERB_APP_KEY="chat-qa-key",
           VITE_REVERB_HOST="127.0.0.1", VITE_REVERB_PORT="8082", VITE_REVERB_SCHEME="http")
processes = []
paused = set()
control = Path(tempfile.gettempdir()) / f"{database}.control"
try:
    for cwd, command in [("BE", ["php", "artisan", "serve:local", "--host=localhost", "--port=8011", "--tries=1"]),
                         ("BE", ["php", "artisan", "reverb:start", "--host=127.0.0.1", "--port=8082"]),
                         ("FE", ["node", "node_modules/vite/bin/vite.js", "--port", "5280"])]:
        processes.append(subprocess.Popen(command, cwd=root / cwd, env=env))
    pt_env = dict(env, FRONTEND_URL="http://localhost:5281", SANCTUM_STATEFUL_DOMAINS="localhost:5281",
                  SESSION_COOKIE="fitness_chat_ui_pt", VITE_API_BASE_URL="http://localhost:8012/api/v1")
    for cwd, command in [("BE", ["php", "artisan", "serve:local", "--host=localhost", "--port=8012", "--tries=1"]),
                         ("FE", ["node", "node_modules/vite/bin/vite.js", "--port", "5281"])]:
        processes.append(subprocess.Popen(command, cwd=root / cwd, env=pt_env))
    commands = {
        0: ("BE", ["php", "artisan", "serve:local", "--host=localhost", "--port=8011", "--tries=1"]),
        1: ("BE", ["php", "artisan", "reverb:start", "--host=127.0.0.1", "--port=8082"]),
    }
    while all(p.poll() is None or i in paused for i, p in enumerate(processes)):
        if control.exists():
            action = control.read_text(encoding="utf-8").strip()
            control.unlink()
            for index, name in [(0, "http"), (1, "reverb")]:
                if action == f"pause-{name}" and index not in paused:
                    paused.add(index)
                    subprocess.run(["taskkill", "/PID", str(processes[index].pid), "/T", "/F"], capture_output=True)
                    processes[index].wait(timeout=5)
                    print(f"Paused {name}", flush=True)
                elif action == f"resume-{name}" and index in paused:
                    cwd, command = commands[index]
                    processes[index] = subprocess.Popen(command, cwd=root / cwd, env=env)
                    paused.remove(index)
                    print(f"Resumed {name}", flush=True)
        time.sleep(.5)
finally:
    for process in processes:
        if process.poll() is None:
            subprocess.run(["taskkill", "/PID", str(process.pid), "/T", "/F"], capture_output=True)
    control.unlink(missing_ok=True)
