from flask import Flask, render_template

app = Flask(__name__)

@app.route('/responseform')
def responseform():
    return render_template('responseform.html')

@app.route('/thankyou')
def thankyou():
    return render_template('thankyou.html')