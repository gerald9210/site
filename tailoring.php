<?php
include_once('php/admin/includes/config.php');
session_start();

// Определите минимальный интервал между запросами в секундах
$minInterval = 30; // 30 секунд

$message = ''; // Инициализация переменной сообщения

// Проверьте, была ли отправлена форма
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Проверьте, была ли форма отправлена слишком быстро
    if (isset($_SESSION['last_form_submit']) && (time() - $_SESSION['last_form_submit']) < $minInterval) {
        $message = "Пожалуйста, подождите " . ($minInterval - (time() - $_SESSION['last_form_submit'])) . " секунд перед отправкой следующей формы.";
    } else {
        // Получите данные формы
        $fname = $_POST['name'] ?? '';
        $emailid = $_POST['email'] ?? '';
        $phonenumber = $_POST['phone'] ?? '';
        $lastDate = $_POST['bookingdate'] ?? '';
        $bookingdate = date("Y-m-d", strtotime($lastDate));
        $bookingtime = $_POST['bookingtime'] ?? '';
        $comment = $_POST['comment'] ?? '';
        $bno = mt_rand(100000000, 9999999999);

        // Проверьте, существует ли этот email в базе данных
        $checkEmailQuery = "SELECT COUNT(*) FROM tblbookings WHERE emailId = ?";
        $stmt = $con->prepare($checkEmailQuery);
        $stmt->bind_param("s", $emailid);
        $stmt->execute();
        $stmt->bind_result($emailExists);
        $stmt->fetch();
        $stmt->close();

        if ($emailExists > 0) {
            $message = "Этот электронный адрес уже зарегистрирован. Пожалуйста, введите другой электронный адрес.";
        } else {
            // Подготовьте запрос на вставку
            $insertQuery = "INSERT INTO tblbookings (bookingNo, fullName, emailId, phoneNumber, bookingDate, bookingTime, comment) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $con->prepare($insertQuery);

            // Привяжите параметры и выполните запрос
            $stmt->bind_param("sssssss", $bno, $fname, $emailid, $phonenumber, $bookingdate, $bookingtime, $comment);

            // Выполните запрос
            if ($stmt->execute()) {
                $message = "Ваш заказ успешно отправлен! Ваш номер заказа: " . $bno;
                // Обновите время последней отправки формы в сессии
                $_SESSION['last_form_submit'] = time();
            } else {
                $message = 'Что-то пошло не так, попробуйте еще раз';
            }

            // Закройте запрос
            $stmt->close();
        }
    }
}

// Получите забронированные даты из базы данных
$bookedDates = array();
$result = mysqli_query($con, "SELECT bookingDate FROM tblbookings");
while ($row = mysqli_fetch_assoc($result)) {
    $bookedDates[] = $row['bookingDate'];
}

// Преобразуйте даты в формат, который понимает jQuery UI Datepicker
$bookedDates = array_map(function($date) {
    return date('Y-m-d', strtotime($date));
}, $bookedDates);

// Закодируйте даты в JSON для использования в JavaScript
$bookedDatesJson = json_encode($bookedDates);
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1">
    <link rel="shortcut icon" href="img/314x95.png" type="image/x-icon">
    <meta name="description" content="">


    <title>Пошив</title>
    <link rel="stylesheet" href="web/assets/mobirise-icons2/mobirise2.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-grid.min.css">
    <link rel="stylesheet" href="bootstrap/css/bootstrap-reboot.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="theme/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <link rel="stylesheet" href="https://neave.github.io/wickedpicker/dist/wickedpicker.min.css">

    <script src="https://neave.github.io/wickedpicker/dist/wickedpicker.min.js"></script>
    <link rel="stylesheet" href="rtbs/css/jquery-ui.css" />
    <link href="rtbs/css/wickedpicker.css" rel="stylesheet" type='text/css' media="all" />
    <link rel="preload"
        href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;700&display=swap&display=swap" as="style"
        onload="this.onload=null;this.rel='stylesheet'">
    <noscript>
        <link rel="stylesheet"
            href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;700&display=swap&display=swap">
    </noscript>
    <link rel="preload" as="style" href="css/mbr-additional.css">
    <link rel="stylesheet" href="css/mbr-additional.css" type="text/css">
    <script>
    $(function() {
        // Декодирование дат из JSON и инициализация Datepicker
        var bookedDates = <?php echo $bookedDatesJson; ?>;
        $("#bookingdate").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0,
            beforeShowDay: function(date) {
                var day = date.getDay();
                var dateString = jQuery.datepicker.formatDate('yy-mm-dd', date);
                // Отключаем воскресенье
                if (day === 0) {
                    return [false];
                }
                // Выделяем субботы
                if (day === 6) {
                    return [true, 'highlight'];
                }
                // Проверяем, является ли дата бронированной
                if ($.inArray(dateString, bookedDates) !== -1) {
                    return [false, 'booked'];
                }
                // Все остальные дни разрешены
                return [true];
            }
        });

        // Инициализация маски для поля ввода номера телефона
        $("#phone").mask("+7 (999) 999-99-99");
    });
    </script>
</head>

<body>

    <section data-bs-version="5.1" class="menu menu2 cid-tWZRPA5f1F" once="menu" id="menu02-0">


        <nav class="navbar navbar-dropdown navbar-fixed-top navbar-expand-lg">
            <div class="container">
                <div class="navbar-brand">
                    <span class="navbar-logo">
                        <a href="index.html">
                            <img src="img/314x95.png" alt="logo" style="height: 3rem;">
                        </a>
                    </span>

                </div>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-bs-toggle="collapse"
                    data-target="#navbarSupportedContent" data-bs-target="#navbarSupportedContent"
                    aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
                    <div class="hamburger">
                        <span></span>
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                </button>
                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav nav-dropdown" data-app-modern-menu="true">
                        <li class="nav-item">
                            <a class="nav-link link text-black text-primary display-4" href="tailoring.php">Пошив</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link link text-black text-primary display-4" href="repair.php"
                                aria-expanded="false">Ремонт</a>
                        </li>
                        <li class="nav-item"><a class="nav-link link text-black show text-primary display-4"
                                href="index.html#gallery-1-tWZRPA7StQ">Каталог</a></li>
                    </ul>
                    <div class="icons-menu">
                        <a class="iconfont-wrapper" href="tel:+8-951-9444279">
                            <span class="p-2 mbr-iconfont mobi-mbri-phone mobi-mbri"></span>
                        </a>
                        <a class="iconfont-wrapper" href="mailto:shveyachaik@mail.ru">
                            <span class="p-2 mbr-iconfont mobi-mbri-letter mobi-mbri"></span>
                        </a>
                        <a class="iconfont-wrapper" href="https://maps.app.goo.gl/42HzkfAbvMam5uP98" target="_blank">
                            <span class="p-2 mbr-iconfont mobi-mbri-map-pin mobi-mbri"></span>
                        </a>

                    </div>
                    <div class="navbar-buttons mbr-section-btn"><a class="btn btn-primary display-4"
                            href="index.html#service-list-2-tWZRPA63fg">Заказать<br></a> <a
                            class="btn btn-white display-4" href="php/index.php">Проверить запись<br></a></div>
                </div>
            </div>
        </nav>
    </section>

    <section data-bs-version="5.1" class="form5 cid-tX64TfVxhn" id="form02-3">

        <div class="mbr-overlay"></div>
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-12 content-head">
                    <div class="mbr-section-head mb-5">
                        <h3 class="mbr-section-title mbr-fonts-style align-center mb-0 display-2"
                            style="margin-top: 50px;"><strong>Заказать пошив одежды</strong></h3>
                        <p>
                            <?php if (!empty($message)): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                        <?php endif; ?>
                        </p>
                    </div>
                </div>
                <div class="row justify-content-center mt-4">
                    <div class="col-lg-8 mx-auto mbr-form" data-form-type="formoid">
                        <form action="#" method="post">
                            <div class="personal">
                                <div class="col-12 form-group mb-3" data-for="name">
                                    <input type="text" name="name" placeholder="Ваше имя" data-form-field="name"
                                        class="form-control" value="" id="name-form02-3">
                                </div>
                                <div class="col-12 form-group mb-3" data-for="email">
                                    <input type="email" name="email" placeholder="Электронная почта"
                                        data-form-field="email" class="form-control" value="" id="email-form02-3">
                                </div>
                                <div class="col-12 form-group mb-3" data-for="phone">
                                    <input type="tel" name="phone" placeholder="Телефон для связи"
                                        data-form-field="phone" class="form-control" id="phone">
                                </div>
                                <div class="col-12 form-group mb-3">
                                    <input type="text" id="bookingdate" name="bookingdate"
                                        placeholder="Выберите дату визита" class="form-control">
                                </div>



                                <div class="col-12 form-group mb-3">
                                    <input type="text" id="bookingtime" name="bookingtime"
                                        class="timepicker form-control hasWickedpicker" placeholder="Время" required="" ">
                                </div>
                                    <div class=" clear">
                                </div>
                            </div>

                            <script>
                            $(function() {
                                // Инициализация WickedPicker с начальным временем 8:00
                                $('#bookingtime').wickedpicker({
                                    now: '8:00', // Установка начального времени на 8:00
                                    twentyFour: true, // Использование 24-часового формата
                                    upArrow: 'wickedpicker__controls__control-up', // Класс для кнопки увеличения времени
                                    downArrow: 'wickedpicker__controls__control-down', // Класс для кнопки уменьшения времени
                                    close: 'wickedpicker__close', // Класс для кнопки закрытия
                                    hoverState: 'hover-state', // Класс для состояния при наведении
                                    title: 'Выберите время', // Заголовок для выпадающего списка
                                    showSeconds: false, // Отображение секунд в выпадающем списке
                                    minutesInterval: 30, // Интервал выбора минут
                                    secondsInterval: 1, // Интервал выбора секунд
                                    showNowButton: true, // Отображение кнопки "Теперь"
                                    nowButtonLabel: 'Текущее время', // Надпись на кнопке "Теперь"
                                    nowButtonHandler: function() { // Обработчик кнопки "Теперь"
                                        this.setTime(new Date());
                                        this.close();
                                    },
                                    // ... другие настройки WickedPicker
                                });
                            });
                            </script>
                            <div class="col-12 form-group mb-3" data-for="comment">
                                <textarea name="comment" placeholder="Комментарий по изделию" data-form-field="textarea"
                                    class="form-control" id="textarea-form02-3"></textarea>
                            </div>
                            <div class="clear"></div>
                            <div class="col-lg-12 col-md-12 col-sm-12 align-center mbr-section-btn">
                                <div class="btnn">
                                    <input type="submit" value="Отправить" name="submit"
                                        class="btn btn-primary display-7">
                                </div>

                            </div>




                    </div>
                    </form>

                </div>
            </div>

    </section>

    <section data-bs-version="5.1" class="footer3 cid-tWZRPAayC0" once="footers" id="footer03-2">
        <div class="container">
            <div class="row">
                <div class="row-links">
                    <ul class="header-menu">




                        <li class="header-menu-item mbr-fonts-style display-5">
                            <a href="#" class="text-white">Главная</a>
                        </li>
                        <li class="header-menu-item mbr-fonts-style display-5">
                            <a href="#" class="text-white">Услуги</a>
                        </li>
                        <li class="header-menu-item mbr-fonts-style display-5">
                            <a href="#" class="text-white">О нас</a>
                        </li>
                        <li class="header-menu-item mbr-fonts-style display-5">
                            <a href="#" class="text-white">Контакты</a>
                        </li>
                    </ul>
                </div>

                <div class="col-12 mt-4">
                    <div class="social-row">
                        <div class="soc-item">
                            <a href="#" target="_blank">
                                <span class="mbr-iconfont display-7 socicon-vkontakte socicon"></span>
                            </a>
                        </div>
                        <div class="soc-item">
                            <a href="#" target="_blank">
                                <span class="mbr-iconfont socicon-instagram socicon"></span>
                            </a>
                        </div>



                    </div>
                </div>
                <div class="col-12 mt-5">
                    <p class="mbr-fonts-style copyright display-7">© 2023 Ателье Любовь Максимова. Все права
                        защищены.
                    </p>
                </div>
            </div>
        </div>
    </section>
    <section class="display-7"
        style="padding: 0;align-items: center;justify-content: center;flex-wrap: wrap;    align-content: center;display: flex;position: relative;height: 4rem;">
    </section>
    <script src="js/main.js"></script>
    <script src="bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="js/smooth-scroll.js"></script>
    <script src="js/index.js"></script>
    <script src="js/navbar-dropdown.js"></script>
    <script src="theme/js/script.js"></script>
    <script src="js/formoid.min.js"></script>

    <script src="php/js/jquery-ui.js"></script>
    <script>
    $(function() {
        $("#datepicker,#datepicker1,#datepicker2,#datepicker3").datepicker();
    });
    </script>
    <!-- //Calendar -->
    <!-- Time -->
    <script type="text/javascript" src="rtbs/js/wickedpicker.js"></script>
    <script type="text/javascript">
    $('#bookingtime').wickedpicker({
        twentyFour: true, // Использование 24-часового формата
    });
    </script>

</body>

</html>