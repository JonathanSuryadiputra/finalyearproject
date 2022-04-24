<?php
function getSentiment($text) {
    $response = file_get_contents('https://sentiment-ykmvrmi3zq-nw.a.run.app/analyse?text=' . urlencode($text));
    $json = json_decode($response);
    $sentiment_weight = $json->{'sentiment'};
    return $sentiment_weight;
}

function buildOutcomePage($reviewText, $sentiment) {
    echo "<html>
        <head>
            <title>Sentiment Result</title>
            <link rel='stylesheet' href='style.css'>
            <link rel='icon' type='image/png' href='favicon.png'>
        </head>
        <body>
            <div class='outcomecontainer'>
                <img src='pos-logo-white.png' width='300px'>
                <h1>Sentiment Result</h1>
                <h2>Review text:</h2>
                <p style='width: 50%; text-align: center; text-justify: inter-word; line-height: 1.5;'>" . $reviewText . "</p>
                <h3>Sentiment weighting: " . $sentiment . "</h3>
            </div>
        </body>
    </html>";
}

if(isset($_POST['review'])) {
    $reviewText = $_POST['review'];
    $sentiment = getSentiment($reviewText);
    buildOutcomePage($reviewText, $sentiment);
}
?>