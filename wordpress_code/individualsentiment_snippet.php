<?php
// get the group ID from the URL parameter through GET method.
$groupId = $_GET['group-id'];
$forms = $wpdb->get_results("SELECT * FROM wpqc_sentimentforms");
if (isset($_POST['forms'])) {
    $formId = $_POST['forms']; // variable to control which form to show, can be changed to a value from POST parameter.
}
else {
    $formId = $forms[0]->form_id;  // if the form ID is not set from the POST method, set it to the first one from the forms SQL query result.
}
$students = $results = $wpdb->get_results("SELECT * FROM wpqc_students WHERE group_id = '" . $groupId . "'");
if (isset($_POST['students'])) {
    $selectedStudent = $_POST['students']; // variable to control which student data to show, can be changed to a value from POST parameter.
}
else {
    $selectedStudent = $students[0]->student_id; // if the student ID is not set from the POST method, set it to the first one from the students SQL query result.
}

// get individual student feedback sentiments for the Y values of the chart.
function getStudentScores($wpdb, $studentId, $formId) {
    $scores = [];
    $results = $wpdb->get_results("SELECT * FROM wpqc_student_sentiments WHERE student_id = '" . $studentId . "' AND form_id = '" . $formId . "'");
    for ($i = 0; $i < count($results); $i++) {
        $currentRow = $results[$i];
        $scores[$currentRow->question_id] = $currentRow->sentiment_weight;
    }
    return $scores;
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

// get student data from MySQL through a query request and print them out into a select box. Allows user to select which student's feedback sentiments they want to view.
function getStudents($wpdb, $groupId) {
    $results = $wpdb->get_results("SELECT * FROM wpqc_students WHERE group_id = '" . $groupId . "'");
    echo "<label for='students'><b>Selected student:</b></label><br>";
    echo "<select name='students' id='students' onchange='submitForm()'>";
    for ($i = 0; $i < count($results); $i++) {
        $currentRow = $results[$i];
        if (isset($_POST['students'])) {
            if ($currentRow->student_id == $_POST['students']) {
                echo "<option value='" . $currentRow->student_id . "' selected='selected'>" . $currentRow->student_firstname . " " . $currentRow->student_lastname . "</option>";
            }
            else {
                echo "<option value='" . $currentRow->student_id . "'>" . $currentRow->student_firstname . " " . $currentRow->student_lastname . "</option>";
            }
        }
        else {
            echo "<option value='" . $currentRow->student_id . "'>" . $currentRow->student_firstname . " " . $currentRow->student_lastname . "</option>";
        }
    }
    echo "</select>";
}

// get the name of the school that the students belong to (this is the same as the group name).
function getSchoolName($wpdb, $groupId) {
    $results = $wpdb->get_results("SELECT group_name FROM wpqc_groups WHERE group_id = '" . $groupId . "'");
    return $results[0]->group_name;
}

// echo out the HTML elements.
echo "<div style='width: 100%; height: 100%;'>
    <div style='display: flex; align-items: center;'>
        <button type='button' onclick='window.location.href=\"/group-management/?group-id=" . $groupId . "\"'>Back</button>
        <h2 style='margin-left: 15px;'>Student Sentiment Scores</h2>
    </div>
    <div style='width: 100%; display: flex; align-items: center; justify-content: space-between; padding: 20px;'>
        <div style='width: 45%;'>
<b>School Name:</b><br>
" . getSchoolName($wpdb, $groupId) . "<br>
            <form id='form' action='' method='post'>"; // print out the school name as well.
getForms($wpdb); // gets the forms using the getForms() function specified above, and put them inside a form element.
echo "<br>";
getStudents($wpdb, $groupId); // gets the students using the getStudents() function specified above, and put them inside a form element.
echo "      </form>
        </div>
        <button type='button' onclick='printPdf()' style='height: 50px;'>Print PDF</button>
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
$question_keys = array_keys($questions); // and the keys of the questions. This is used to access the score data from a map.

// print out the questions into elements inside a JavaScript list from PHP.
$questionList = 'var questions = [';
for ($i = 0; $i < count($question_keys); $i++) {
    $questionList = $questionList . "'" . $questions[$question_keys[$i]] . "',";
}
$questionList = $questionList . '];';
echo $questionList;

// get the student's response sentiments towards the selected form and print them into a JavaScript list.
$sentiments = getStudentScores($wpdb, $selectedStudent, $formId);
$scores = 'var scores = [';
for ($i = 0; $i < count($question_keys); $i++) {
    $scores = $scores . $sentiments[$question_keys[$i]] . ',';
}
$scores = $scores . '];';
echo $scores;

// echo out JavaScript code to render the chart using Chart.js.
// Tooltip for the data points is configured to display the long question strings x-axis labels while keeping the actual annotated x-axis short to provide a clean look.
// the scores list is set as the y-axis (data), while the questions are designated as the x-axis (labels)
echo "  function createShortLabels(count) {
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
                        label: 'Sentiment Score',
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