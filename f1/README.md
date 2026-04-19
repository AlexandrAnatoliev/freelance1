<div align="center">
  <a id="russian"></a>
  <h1>Скрипт php</h1>

  ![Version 0.1.4](https://img.shields.io/badge/Version-0.1.4-orange.svg)
  ![Stars](https://img.shields.io/github/stars/AlexandrAnatoliev/freelance.svg?style=flat)
  ![Forks](https://img.shields.io/github/forks/AlexandrAnatoliev/freelance.svg?style=flat)
  ![GitHub repo size](https://img.shields.io/github/repo-size/AlexandrAnatoliev/freelance)
  
</div>

  > **Author:** Alexandr Anatoliev

  > **GitHub:** [AlexandrAnatoliev](https://github.com/AlexandrAnatoliev)

---

<div align="center">
  <h2>Навигация</h2>
</div>

* [Техническое задание](#technical-specifications)
* [Общая архитектура](#architecture)

---

<div align="center">
  <a id="technical-specifications"></a>
  <h2>Техническое задание</h2>
</div>

```
Нужен скрипт калькулятора-заказа на php с радиокнопками, чекбоксами, 
картинками, полем ввода количества, расчётом итоговой суммы заказа 
и отправкой готового счетана оплату(в pdf или html с возможностью 
сохранения покупателем из письма в pdf) на почту покупателя и админа. 
Проведение онлайн оплаты не нужно, только отправка.
```

#### Реализовано:
* Страница заказа
<div align="center">
  <img src="img/index.png" width=640>
</div>
* Счет на оплату:
<div align="center">
  <img src="img/calculate.png" width=640>
</div>
* Сохранение в pdf-файл:
<div align="center">
  <img src="img/save-in-pdf.png" width=640>
</div>
* Отправка письма на почту покупателю:
<div align="center">
  <img src="img/phone-mail.jpg" width=320>
</div>

---

<div align="center">
  <a id="architecture"></a>
  <h2>Общая архитектура</h2>
</div>


```mermaid
classDiagram
  
  class index.php {
  }

  class calculate.php {
  }

  class mail_config.php {
  }

  class SMTP-сервер {
  }

  index.php --|> calculate.php
  calculate.php --|> mail_config.php
  mail_config.php --|> SMTP-сервер
```
