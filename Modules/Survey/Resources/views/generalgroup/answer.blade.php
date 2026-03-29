<!doctype html>
<html lang="en">

<head>
    <title>Survey</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {

            font-family: Arial, sans-serif;
        }

        .form-container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px 40px;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
        }

        h1 {
            font-size: 28px;
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }

        label {
            font-weight: bold;
            margin-top: 10px;
        }

        input,
        select {
            width: 100%;
            padding: 8px 10px;
            margin-top: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-check {
            margin-bottom: 10px;
        }

        button {
            width: 100%;
            background-color: rgb(37, 126, 161);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background-color: rgb(36, 94, 121);
        }

        .form-check-label {
            font-size: 14px;
        }

        .form-check {
            margin-bottom: 10px;
        }

        .star-rating .fa-star {
            font-size: 20px;
            color: #ddd;
            cursor: pointer;
            margin-right: 5px;
        }

        .star-rating .fa-star.checked {
            color: #f39c12;
        }

        .choice {
            font-size: 17px;
            margin-top: 0px;
            margin-left: 10px;

        }

        p {
            margin: 0;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <form>
            <input type="hidden" name="surveyId" id="surveyId" value="<?= $survey->id ?? '' ?>">

            <h1><?= htmlspecialchars($survey->title) ?></h1>
            <p><?= htmlspecialchars($survey->description) ?></p>

            <div class="form-section">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" placeholder="Enter Name" value = "<?= $data->name ?>" disabled>

                <label for="email" style="margin-top: 15px;">Email:</label>
                <input type="email" id="email" name="email" placeholder="Enter Email" value = "<?= $data->email ?>" disabled>

                <label for="phone" style="margin-top: 15px;">Phone:</label>
                <input type="text" id="phone" name="phone" placeholder="Enter Phone" value = "<?= $data->phone ?>" disabled>
            </div>

            <?php foreach ($questions as $q) { ?>
            <label style="margin-top: 15px;"> <?= htmlspecialchars($q->text) ?> </label>
            <?php $response = DB::table('response_general_group')->select('answer')->where('number_of_fill', $data->number_of_fill)->where('survey_id', $survey->id)->where('question_id', $q->id)->first(); ?>

            <?php if ($q->description === null) { ?>
            <input type="text" id="question_<?= $q->id ?>" name="answers[<?= $q->id ?>]"
                placeholder="Enter your answer" value="<?= $response->answer ?>" disabled>
            <?php } elseif ($q->type_id == 2) { ?>
            <?php $options = json_decode($q->description, true); ?>
            <?php foreach ($options as $index => $option) { ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="answers[<?= $q->id ?>]"
                    id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($option) ?>"
                    <?= $option == $response->answer ? 'checked' : '' ?> disabled>
                <label class="choice" for="question_<?= $q->id ?>option<?= $index ?>">
                    <?= htmlspecialchars($option) ?> </label>
            </div>
            <?php } ?>
            <?php } elseif($q->type_id == 4) { ?>
            <?php $rate = json_decode($q->description, true); ?>
            <div class="star-rating">
                <?php for($i = $rate[0]; $i <= $rate[1]; $i++) { ?>
                <span class="fa fa-star <?= $i <= $response->answer ? 'checked' : '' ?>" data-value="<?= $i ?>"></span>
                <?php } ?>
            </div>
            <?php } else { ?>
            <?php $options = json_decode($q->description, true); ?>
            <?php $answers = json_decode($response->answer, true); ?>
            <?php foreach ($options as $index => $option) { ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="answers[<?= $q->id ?>][]"
                    id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($option) ?>"
                    <?= in_array($option, $answers) ? 'checked' : '' ?> disabled>
                <label class="choice" for="question_<?= $q->id ?>option<?= $index ?>">
                    <?= htmlspecialchars($option) ?> </label>
            </div>
            <?php } ?>
            <?php } ?>
            <?php } ?>



        </form>
    </div>
    
</body>

</html>
