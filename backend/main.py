# import necessary dependencies
import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'

import pickle
import re
import numpy as np
from keras.models import load_model
from keras.preprocessing.sequence import pad_sequences

from flask import Flask, request, jsonify
from flask_cors import CORS, cross_origin

model = load_model('lstm_model.h5') # load LSTM sentiment analysis data model.
tokeniser = pickle.load(open('tokeniser.p', 'rb')) # load pickle file of the text tokeniser.

sarcasm_detector = load_model('sarcasm_detector.h5') # load LSTM sarcasm detector model
sarcasm_tokeniser = pickle.load(open('sarcasm_tokeniser.p', 'rb')) # load sarcasm word tokeniser; not the same as the regular tokeniser as it has different configurations.

def convertToNeg(N):
    '''
    This function converts an integer N to its negative sign counterpart.
    Parameters:
        N (int): integer to be converted
    '''
    if N<0:
        # check if N is less then 0 already, if it is, simply return N as is.
        return N
    else:
        # else substract N by itself times 2 in order to put a negative sign on N.
        return N-(N*2)

def clean_text(text):
    '''
    This function cleans input text the same way text data samples were cleaned before the modelling process.
    Parameters:
        text (string): input text to be cleaned
    '''
    text = text.lower() # convert all characters to lowercase
    text = text.strip() # remove any leading or trailing spaces
    text = re.sub(r'\d+', '', text) # remove any digits or numbers
    text = re.sub(r'<br>', '', text) # remove HTML line break
    text = re.sub(r'<br />', '', text) # remove HTML line break closing tag
    text = re.sub(r'[.,:;/\\$£&^*%"(){}\[\]<>#@…+-=_~|¬`]', '', text) # remove punctuations (except for '!' and '?')
    text = re.sub(r"'", '', text) # remove single quotes
    text = re.sub(r' +', ' ', text) # remove white spaces that are contiguously more than one
    return text

def get_sentiment(text):
    '''
    This utilises the sentiment analysis model and returns the model's predicted sentiment polarity.
    Parameters:
        text (string): input text to be predicted for its polarity sentiment
    '''
    seq = tokeniser.texts_to_sequences([text]) # put text in a list and use tokeniser to convert the text to sequence
    # putting text in a list as the function only accepts lists of strings not individual strings
    padded = pad_sequences(seq, maxlen=500, padding='post', truncating='post') # pad the sequence
    pred = model.predict(padded) # predict padded sequence of text
    predSentiment = np.argmax(pred)-3 # return the integer label substracted by 3 to match the software requirements of labels between -2 to 2 (original labels are 1 to 5)
    return predSentiment # return the sentiment score

def detect_sarcasm(text):
    '''
    This function utilises the sarcasm detector model and returns the model's prediction of the presence of sarcasm within the text.
    Parameters:
        text (string): input text to be checked for sarcastic undertones
    '''
    # process is similar to the get_sentiment(text) function
    seq = sarcasm_tokeniser.texts_to_sequences([text])
    padded = pad_sequences(seq, maxlen=100, padding='post', truncating='post')
    pred = sarcasm_detector.predict(padded)
    isSarcastic = np.argmax(pred) # returns either a 0 (no sarcasm detected) or 1 (sarcasm detected)
    return isSarcastic # return prediction to main control

app = Flask(__name__) # initialise Flask app container
cors = CORS(app, resources={r"/api/*": {'origin': '*'}}) # set CORS origin to all
app.config['CORS_HEADERS'] = 'Content-Type'

@app.route('/analyse', methods=['GET']) # set Flask routing for the analyse() function
@cross_origin()
def analyse():
    '''
    This function is hooked to the /analyse endpoint route and calls several of the above functions to complete its intended task
    of returning a sentiment score for a given text within its URL parameters.
    '''
    text = str(request.args.get('text')) # fetch the text to be analysed using the GET method
    text = clean_text(text) # clean the text
    sentiment_weight = get_sentiment(text) # get the polarity sentiment of the text
    if detect_sarcasm(text) == 1: # if sarcasm is detected
        sentiment_weight = convertToNeg(sentiment_weight) # convert sentiment score sign to negative
    return jsonify({'sentiment': int(sentiment_weight)}) # package sentiment output to JSON format and return it to the GET method request.

if __name__ == '__main__':
    '''
    Main app function.
    '''
    app.run(debug=True)