import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import pickle
import re
import numpy as np
from keras.models import load_model
from keras.preprocessing.sequence import pad_sequences

from flask import Flask, request, jsonify

model = load_model('model.h5')
tokeniser = pickle.load(open('tokeniser.p', 'rb'))

def get_sentiment(text):
    text = text.lower()
    text = text.strip()
    text = re.sub(r'\d+', '', text)
    text = re.sub(r'<br>', '', text)
    text = re.sub(r'<br />', '', text)
    text = re.sub(r'[.,:;/\\$£&^*%"(){}\[\]<>#@…+-=_~|¬`]', '', text)
    text = re.sub(r"'", '', text)
    text = re.sub(r' +', ' ', text)
    processed = [text]
    seq = tokeniser.texts_to_sequences(processed)
    padded = pad_sequences(seq, maxlen=600, padding='post', truncating='post')
    pred = model.predict(padded)
    predSentiment = np.argmax(pred)-3
    return predSentiment

app = Flask(__name__)

@app.route('/analyse', methods=['GET'])
def analyse():
    text = str(request.args.get('text'))
    sentiment_weight = get_sentiment(text)
    return jsonify({'sentiment': int(sentiment_weight)})

if __name__ == '__main__':
    app.run(debug=True)