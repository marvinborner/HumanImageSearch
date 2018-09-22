from PIL import Image, ImageDraw
from io import BytesIO
import base64
import face_recognition
import json
import numpy as np
import requests
import signal
import sys

def exit_handler(signum, frame):
    print('Exited face detection.')

signal.signal(signal.SIGINT, exit_handler)

response = requests.get(sys.argv[1])
user_image = Image.open(BytesIO(response.content))
image = np.array(user_image)

face_landmarks_list = face_recognition.face_landmarks(image)
face_count = len(face_landmarks_list)

pil_image = Image.fromarray(image)
d = ImageDraw.Draw(pil_image)

for face_landmarks in face_landmarks_list:
    for facial_feature in face_landmarks.keys():
        d.line(face_landmarks[facial_feature], width=3)

feature_buffered = BytesIO()
pil_image.save(feature_buffered, format="JPEG")
image_feature_str = base64.b64encode(feature_buffered.getvalue())

normal_buffered = BytesIO()
user_image.save(normal_buffered, format="JPEG")
image_str = base64.b64encode(normal_buffered.getvalue())

print(json.dumps({'count': str(face_count), 'feature': str(image_feature_str, 'utf-8'), 'normal': str(image_str, 'utf-8')}))