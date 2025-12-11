<?php require_once('header.php'); ?>
<?php include_once('error.php'); ?>
<?php include_once('functions.php'); ?>

<?php
// Verificar si el formulario fue enviado

$nutritionInfo = '';
$caloriesInfo = '';

if (isset($_GET['submit_calories'])) {
    // Preparar la consulta para obtener la suma de las calorías por user_id
    $stmt_calories = $conexion->prepare("SELECT SUM((calories*total)/100) AS total_calories FROM food_plan");
    // Mi consulta consiste en linea por linea multiplicar el total de gramos por las calorias en KCAL y eso dividir para 100g porque la API nos devuelve datos de KCAL con unidad de 100g

    // Ejecutar la consulta
    if ($stmt_calories->execute()) {
        $result = $stmt_calories->get_result();
        // Si hay un resultado, obtener la suma de las calorías
        if ($row = $result->fetch_assoc()) {
            $total_calories = $row['total_calories'];
            if ($total_calories > 0) {
                $caloriesInfo = "<p>Your total calories consumption until now is " . number_format($total_calories, 2, '.', '') . " kcal</p>"; //Indicamos total de KCAL consumidas con base en los alimentos registrados
            } else {
                $caloriesInfo = "<p>No calories has been consumed.</p>"; //Si no ha consumido calorias lo indicamos al usuario
            }
        } else {
            $caloriesInfo = "<p>No food records found for your user to calculate calories. Please add foods</p>"; //Si no tiene registros también le indicamos
        }
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt_calories->error . "</div>";
    }

    // Cerrar la conexión
    $stmt_calories->close();
}

if(isset($_POST['submit_reset_food'])){

    // Preparar la consulta para borrar los registros de la base
    $stmt_reset = $conexion->prepare("TRUNCATE food_plan");
    
    // Ejecutar la consulta
    if ($stmt_reset->execute()) {
        echo "<div class='alert alert-success'>Your list of foods have been reseted</div>";
    } else {
        echo "<div class='alert alert-danger'>Error: " . $stmt_reset->error . "</div>";
    }

    // Cerrar la conexión
    $stmt_reset->close();
}

if (isset($_POST['submit_food'])) {
    // Obtener datos del formulario
    $food = htmlspecialchars($_POST['food']);
    $amount = htmlspecialchars($_POST['amount']);
    $measure = htmlspecialchars($_POST['measure']);
    $total = $measure * $amount;

    // Configurar la API de la USDA
    $api_url = 'https://api.nal.usda.gov/fdc/v1/foods/search';
    $api_key = apikey();

    // Preparar datos para la solicitud a la API
    $data = [
        'query' => $food,
        'pageSize' => 1,
        'api_key' => $api_key
    ];

    // Realizar la solicitud cURL
    $ch = curl_init($api_url . '?' . http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    // Decodificar la respuesta JSON de la API
    $result = json_decode($response, true);

    $nutrientes=$result['foods'][0]['foodNutrients'];

    // Si encontramos la información de nutrientes
    if (!empty($nutrientes)) {

        $nutritionInfo = '';

        $calories = $protein = $fat = $carbohydrates = $fiber = $sugars = null;
        $cholesterol = $sodium = $vitamin_c = $calcium = $iron = null;
        $vitamin_b12 = $potassium = $magnesium = $nitrogen = $alcohol = null;

        // Recorrer los nutrientes
        foreach ($result['foods'][0]['foodNutrients'] as $nutrient) {
            $value = $nutrient['value'];
            switch ($nutrient['nutrientName']) {
                case 'Potassium, K':
                    if ($nutrient['unitName'] === 'MG') {
                        $potassium = $value;
                    }
                    break;
                case 'Magnesium':
                    if ($nutrient['unitName'] === 'MG') {
                        $magnesium = $value;
                    }
                    break;
                case 'Energy':
                    if ($nutrient['unitName'] === 'KCAL') {
                        $calories = $value;
                    }
                    break;
                case 'Protein':
                    if ($nutrient['unitName'] === 'G') {
                        $protein = $value;
                    }
                    break;
                case 'Total lipid (fat)':
                    if ($nutrient['unitName'] === 'G') {
                        $fat = $value;
                    }
                    break;
                case 'Carbohydrate, by difference':
                    if ($nutrient['unitName'] === 'G') {
                        $carbohydrates = $value;
                    }
                    break;
                case 'Fiber, total dietary':
                    if ($nutrient['unitName'] === 'G') {
                        $fiber = $value;
                    }
                    break;
                case 'Sugars, total including NLEA':
                    if ($nutrient['unitName'] === 'G') {
                        $sugars = $value;
                    }
                    break;
                case 'Cholesterol':
                    if ($nutrient['unitName'] === 'MG') {
                        $cholesterol = $value;
                    }
                    break;
                case 'Sodium, Na':
                    if ($nutrient['unitName'] === 'MG') {
                        $sodium = $value;
                    }
                    break;
                case 'Vitamin C, total ascorbic acid':
                    if ($nutrient['unitName'] === 'MG') {
                        $vitamin_c = $value;
                    }
                    break;
                case 'Calcium, Ca':
                    if ($nutrient['unitName'] === 'MG') {
                        $calcium = $value;
                    }
                    break;
                case 'Iron, Fe':
                    if ($nutrient['unitName'] === 'MG') {
                        $iron = $value;
                    }
                    break;
            }
        }

        // Preparar y ejecutar la inserción en la base de datos
        $stmt = $conexion->prepare("INSERT INTO food_plan (name, amount, measurement, total, calories, protein, fat, carbohydrates, fiber, sugars, cholesterol, sodium, vitamin_c, calcium, iron, potassium, magnesium, nitrogen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssidssssssssssssss", $food, $measure, $amount, $total, $calories, $protein, $fat, $carbohydrates, $fiber, $sugars, $cholesterol, $sodium, $vitamin_c, $calcium, $iron, $potassium, $magnesium, $nitrogen);

        // Ejecutar la consulta
        if ($stmt->execute()) {
            echo "<div class='alert alert-success'>Record inserted successfully</div>";
        } else {
            echo "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }

        // Cerrar la conexión
        $stmt->close();

        // Mostrar la información de nutrientes
        $nutritionInfo .= "<h3>Nutrition Information for $food:</h3>";
        $nutritionInfo .= "<ul>";
        $nutritionInfo .= "<li><strong>Calories:</strong> $calories kcal</li>";
        $nutritionInfo .= "<li><strong>Protein:</strong> $protein g</li>";
        $nutritionInfo .= "<li><strong>Fat:</strong> $fat g</li>";
        $nutritionInfo .= "<li><strong>Carbohydrates:</strong> $carbohydrates g</li>";
        $nutritionInfo .= "<li><strong>Fiber:</strong> $fiber g</li>";
        $nutritionInfo .= "<li><strong>Sugars:</strong> $sugars g</li>";
        $nutritionInfo .= "<li><strong>Cholesterol:</strong> $cholesterol mg</li>";
        $nutritionInfo .= "<li><strong>Sodium:</strong> $sodium mg</li>";
        $nutritionInfo .= "<li><strong>Vitamin C:</strong> $vitamin_c mg</li>";
        $nutritionInfo .= "<li><strong>Calcium:</strong> $calcium mg</li>";
        $nutritionInfo .= "<li><strong>Iron:</strong> $iron mg</li>";
        $nutritionInfo .= "<li><strong>Potassium:</strong> $potassium mg</li>";
        $nutritionInfo .= "<li><strong>Magnesium:</strong> $magnesium mg</li>";
        $nutritionInfo .= "</ul>";
    } else {
        $nutritionInfo = "<div class='alert alert-danger'>No information found for {$food}. Try again!</div>";
    }
}
?>

<h1>Enter food to calculate nutrition</h1>
<!-- Se genera el formulario para ingresar la comida y con la API recabar su información -->
<form action="" method="post">
   <div class="form-group form-group-lg">
     <label for="food">Enter food item (be descriptive):</label>
     <input type="text" id="food" name="food" class="form-control" placeholder="red delicious apples / hard boiled eggs" required>
   </div>
   <div class="form-group form-group-lg">
     <label for="amount">Enter how many:</label>
     <input type="number" id="amount" name="amount" class="form-control" required>
   </div>
   <div class="form-group form-group-lg">
     <label for="measure">Choose Measurement Scale:</label>
     <select id="measure" name="measure" class="form-control" required>
       <option value="">Choose</option>
       <option value="240">Cup(s) 240 G</option>
       <option value="120">1/2 Cup(s) 120 G</option>
       <option value="3899">Gallon(s) 3899 G</option>
       <option value="1000">Liter(s) 1000 G</option>
       <option value="38">Slice(s) 38 G</option>
       <option value="50">Piece(s) 50 G</option>
       <option value="5.69">Teaspoon(s) 5.69 G</option>
       <option value="14.175">Tablespoon(s) 14.175 G</option>
       <option value="28.35">Ounce(s) 28.35 G</option>
       <option value="113">Stick(s) 113 G</option>
       <option value="800">Loaf(s) 800 G</option>
       <option value="106">Can(s) 3.75oz 106 G</option>
       <option value="425.24">Can(s) 15oz 425.24 G</option>
       <option value="340.19">Can(s) 12oz 340.19 G</option>
     </select>
   </div>
   <button type="submit" name="submit_food" class="btn btn-success">Add Food</button>
</form>
<br>
<!-- Se genera este div para llenarlo con la info del alimento que recabamos con la API -->
<div class="FoodDescriptionDiv">
    <?php echo $nutritionInfo; ?>
</div>
<br>
<div class="caloriesDiv" style="font-weight: bold;">
     <p> Have you finished entering your diet foods? If Yes, please press "Calculate Calories" button if not please keep registering Foods</p>
</div>
<br>
<form action="" method="get">
    <!-- Este es el botón para calcular calorías -->
    <button type="submit" name="submit_calories" class="btn btn-success">Calculate Calories</button>
</form>
<br>
<div class="CaloriesDescriptionDiv">
    <!-- Div para mostrar total de calorías -->
    <?php echo $caloriesInfo; ?>
</div>
<br>
<div class="caloriesDiv" style="font-weight: bold;">
     <p> If you want to reset your foods please press Reset</p>
</div>
<form action="" method="post">
    <br>
    <!-- Este es el botón para resetear los registros de comida de la tabla de la base -->
    <button type="submit" name="submit_reset_food" class="btn btn-success">Reset</button>
</form>

<?php require_once('footer.php'); ?>

