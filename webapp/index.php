<?php
function getSentiment($text) {
    $response = file_get_contents('https://sentiment-ykmvrmi3zq-nw.a.run.app/analyse?text=' . urlencode($text));
    $json = json_decode($response);
    $sentiment_weight = $json->{'sentiment'};
    return $sentiment_weight;
}

function buildPage() {
    echo "<html>
        <head></head>
        <body>
            <form action='index.php' method='POST'>
                <label for='review'>Enter text here!</label><br>
                <textarea id='review' name='review'></textarea><br>
                <button type='submit'>Submit</button>
            </form>
        </body>
    </html>
    ";
}

if(isset($_POST['review'])) {
    $reviewText = $_POST['review'];
    $sentiment = getSentiment($reviewText);
    echo 'The review text is: ' . $reviewText . '<br>';
    echo 'The sentiment weighting is: ' . $sentiment;
}
else {
    buildPage();
}
?>