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
        <form action="{{ route('surveys.store.fill') }}" method="POST">
            @csrf
            <input type="hidden" name="surveyId" id="surveyId" value="<?= $survey->id ?? '' ?>">
            <input type="hidden" name="user_id" id="user_id" value="<?= $user->id ?>">
            <input type="hidden" name="action_id" id="action_id" value="<?= $action->id ?>">
            <input type="hidden" name="old_survey_id" id="old_survey_id" value="<?= $action->survey_id ?>">

            <h1><?= htmlspecialchars($survey->title) ?></h1>
            <p>Hello <?= $user->name ?></p>
            <p><?= htmlspecialchars($survey->description) ?></p>

            <?php foreach ($questions as $q) { ?>
            <label> <?= htmlspecialchars($q->text) ?> </label>

            <?php if ($q->description === null) { ?>
            <input type="text" id="question_<?= $q->id ?>" name="answers[<?= $q->id ?>]"
                placeholder="Enter your answer" required>

            <?php } elseif ($q->type_id == 2) { ?>
            <?php $options = json_decode($q->description, true); ?>
            <?php foreach ($options as $index => $option) { ?>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="answers[<?= $q->id ?>]"
                    id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($option) ?>" required>
                <label class="choice" for="question_<?= $q->id ?>option<?= $index ?>">
                    <?= htmlspecialchars($option) ?>
                </label>
            </div>
            <?php } ?>

            <?php } elseif ($q->type_id == 4) { ?>
            <?php $rate = json_decode($q->description, true); ?>
            <div class="star-rating" data-question-id="<?= $q->id ?>">
                <?php for ($i = $rate[0]; $i <= $rate[1]; $i++) { ?>
                <span class="fa fa-star" data-value="<?= $i ?>"></span>
                <?php } ?>
                <input type="hidden" name="answers[<?= $q->id ?>]" id="star-input-<?= $q->id ?>" value="">
            </div>

            <?php } else { ?>
            <?php $options = json_decode($q->description, true); ?>
            <?php foreach ($options as $index => $option) { ?>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="answers[<?= $q->id ?>][]"
                    id="question_<?= $q->id ?>option<?= $index ?>" value="<?= htmlspecialchars($option) ?>">
                <label class="choice" for="question_<?= $q->id ?>option<?= $index ?>">
                    <?= htmlspecialchars($option) ?>
                </label>
            </div>
            <?php } ?>
            <?php } ?>
            <?php } ?>

            <button type="submit">Submit</button>

        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".star-rating").forEach(starContainer => {
                const stars = starContainer.querySelectorAll(".fa-star");
                const hiddenInput = starContainer.querySelector("input[type='hidden']");

                stars.forEach(star => {
                    star.addEventListener("click", function() {
                        const value = this.getAttribute("data-value");
                        hiddenInput.value = value;

                        stars.forEach(s => s.classList.remove("checked"));
                        for (let i = 0; i < value; i++) {
                            stars[i].classList.add("checked");
                        }
                    });

                    star.addEventListener("mouseover", function() {
                        stars.forEach(s => s.classList.remove("checked"));
                        for (let i = 0; i < this.getAttribute("data-value"); i++) {
                            stars[i].classList.add("checked");
                        }
                    });

                    starContainer.addEventListener("mouseleave", function() {
                        stars.forEach(s => s.classList.remove("checked"));
                        for (let i = 0; i < hiddenInput.value; i++) {
                            stars[i].classList.add("checked");
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>
