import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import pickle
import re
import spacy
import nltk
import numpy as np
from keras.models import load_model
from keras.preprocessing.sequence import pad_sequences

from flask import Flask, request, jsonify

model = load_model('model.h5')
tokeniser = pickle.load(open('tokeniser.p', 'rb'))

nltk.download('punkt')
nltk.download('stopwords')
nlp = spacy.load('en_core_web_sm', disable=['parser', 'ner'])

def remove_stopwords_lemmatise(text):
    stopwords = nltk.corpus.stopwords.words('english')
    words = [token.lemma_ for token in nlp(text) if not token.is_punct]
    words = [word.lower() for word in words if word.lower() not in stopwords]
    return ' '.join(words)

def get_sentiment(text):
    text = text.lower()
    text = text.strip()
    text = re.sub(r'\d+', '', text)
    text = re.sub(r'<br>', '', text)
    text = re.sub(r'<br />', '', text)
    text = re.sub(r'[^\w\s]', '', text)
    text = re.sub(r' +', ' ', text)
    text = remove_stopwords_lemmatise(text)
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