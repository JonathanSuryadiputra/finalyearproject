<?php
// get the group ID from the URL parameter through GET method.
$groupId = $_GET['group-id'];
$forms = $wpdb->get_results("SELECT * FROM wpqc_sentimentforms");
if (isset($_POST['forms'])) {
    $formId = $_POST['forms']; // variable to control which form to show, can be changed to a value from POST parameter.
}
else {
    $formId = $forms[0]->form_id; // if the form ID is not set from the POST method, set it to the first one from the forms SQL query result.
}

// get average group sentiments for the Y values of the chart.
function getAverageQuestionScore($wpdb, $questionId, $formId, $studentIds) {
    $numOfStudents = 0;
    $sentimentSum = 0;
    for ($i = 0; $i < count($studentIds); $i++) {
        $results = $wpdb->get_results("SELECT * FROM wpqc_student_sentiments WHERE student_id = '" . $studentIds[$i] . "' AND question_id = '" . $questionId . "' AND form_id = '" . $formId . "'");
        if (count($results) != 0) {
            $sentiment = $results[0]->sentiment_weight;
            $sentimentSum += $sentiment;
            $numOfStudents += 1;
        }
    }
    return ($sentimentSum/$numOfStudents);
}

// fish for the question strings which are the X values.
function getQuestions($wpdb, $formId) {
    $returnList = array();
    $questions = $wpdb->get_results("SELECT * FROM wpqc_formquestions WHERE form_id = " . $formId);
    for ($i = 0; $i < count($questions); $i++) {
        $currentQuestion = $questions[$i];
        $returnList[$currentQuestion->question_id] = $currentQuestion->question;
    }
    return $returnList;
}

// get the student IDs from MySQL through a query request. This function will be used in tandem with another function to fetch the average group sentiments.
function getStudentIds($wpdb, $groupId) {
    $results = $wpdb->get_results("SELECT * FROM wpqc_students WHERE group_id = '" . $groupId . "'");
    $studentIds = [];
    for ($i = 0; $i < count($results); $i++) {
        $currentRow = $results[$i];
        array_push($studentIds, $currentRow->student_id);
    }
    return $studentIds;
}

// get form data from MySQL through a query request and print them out into a select box. Allows user to select which form feedback sentiments they want to view.
function getForms($wpdb) {
    $results = $wpdb->get_results("SELECT * FROM wpqc_sentimentforms");
    echo "<label for='forms'><b>Selected form:</b></label><br>";
    echo "<select name='forms' id='forms' onchange='submitForm()'>";
    for ($i = 0; $i < count($results); $i++) {
        $currentRow = $results[$i];
        if (isset($_POST['forms'])) {
            if ($currentRow->form_id == $_POST['forms']) {
                echo "<option value='" . $currentRow->form_id . "' selected='selected'>" . $currentRow->form_name . "</option>";
            }
            else {
                echo "<option value='" . $currentRow->form_id . "'>" . $currentRow->form_name . "</option>";
            }
        }
        else {
            echo "<option value='" . $currentRow->form_id . "'>" . $currentRow->form_name . "</option>";
        }
    }
    echo "</select>";
}

// echo out the HTML elements.
echo "<div style='width: 100%; height: 100%; margin-top: 50px;'>
    <h3><b>Average Group Sentiment</b></h3>
    <div style='width: 100%; display: flex; justify-content: space-between; padding: 20px;'>
        <div style='width: 45%;'>
            <form id='form' action='' method='post'>";
getForms($wpdb); // get the forms using the function specified above, and put them inside a form element.
echo "      </form>
        </div>
        <div style='display: flex; align-items: center;'>
            <button type='button' onclick='printPdf()' style='margin-right: 10px; height: 50px;'>Print PDF</button>
            <button style='margin-left: 10px; height: 50px;' onclick='window.location.href=\"/student-sentiment-view/?group-id=" . $groupId . "\"'>Individual Student Reports</button>
        </div>
    </div>
    <div style='width: 100%;'>
        <canvas id='myChart' height='100px'></canvas>
        <script src='https://cdn.jsdelivr.net/npm/chart.js@2.8.0'></script>
        <script>
            function printPdf() {
                window.print();
            }
            function submitForm() {
                document.getElementById('form').submit();
            }";

// get the questions list.
$questions = getQuestions($wpdb, $formId);
$questionKeys = array_keys($questions); // and the keys of the questions. This is used to access the score data from a map.

// print out the questions into elements inside a JavaScript list from PHP.
$questionList = 'var questions = [';
for ($i = 0; $i < count($questionKeys); $i++) {
    $questionList = $questionList . "'" . $questions[$questionKeys[$i]] . "',";
}
$questionList = $questionList . '];';
echo $questionList;

// get the average group sentiments and print them into a JavaScript list.
$studentIds = getStudentIds($wpdb, $groupId);
$averageScores = 'var scores = [';
for ($i = 0; $i < count($questionKeys); $i++) {
    $averageScore = getAverageQuestionScore($wpdb, $questionKeys[$i], $formId, $studentIds);
    $averageScores = $averageScores . $averageScore . ',';
}
$averageScores = $averageScores . '];';
echo $averageScores;

// echo out JavaScript code to render the chart using Chart.js.
// Tooltip for the data points is configured to display the long question strings x-axis labels while keeping the actual annotated x-axis short to provide a clean look.
// the scores list is set as the y-axis (data), while the questions are designated as the x-axis (labels)
echo "function createShortLabels(count) {
            var shortLabels = [];
            for (var i = 1; i <= count; i++) {
                shortLabels.push('Question ' + i.toString());
            }
            return shortLabels;
        }
        var shortLabels = createShortLabels(questions.length);
        var ctx = document.getElementById('myChart').getContext('2d');
        var chart = new Chart(ctx, {
            // The type of chart we want to create
            type: 'line',

            // The data for our dataset
            data: {
                labels: shortLabels,
                datasets: [
                    {
                        label: 'Average Sentiment',
                        fill: false,
                        borderColor: 'rgb(255, 99, 132)',
                        data: scores,
                        lineTension: 0
                    }
                ]
            },
            
            options: {
                scales: {
                    yAxes: [{
                        display: true,
                        ticks: {
                            beginAtZero: false,
                            steps: 5,
                            stepValue: 1,
                            max: 2,
                            min: -2
                        }
                    }]
                },
                tooltips: {
                    callbacks: {
                        title: function (tooltipItems) {
                            return questions[tooltipItems[0].index];
                        },
                    }
                }
            }
        });
        </script>
    </div>
</div>";
?>