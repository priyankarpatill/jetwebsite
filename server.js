const express = require('express');
const bodyParser = require('body-parser');
const mysql = require('mysql2');

const app = express();
app.use(bodyParser.urlencoded({ extended: true }));

// Set up your MySQL connection
const db = mysql.createConnection({
  host: 'localhost',
  user: 'your_username',
  password: 'your_password',
  database: 'blusky'
});

db.connect(err => {
  if (err) {
    console.error('DB connection error:', err);
    process.exit(1);
  }
  console.log('Connected to MySQL!');
});

// Handle form submission
app.post('/book', (req, res) => {
  const {
    trip_type,
    departure,
    arrival,
    departure_date,
    return_date = null,
    mobile_number,
    email,
    num_passengers
  } = req.body;

  // For MULTI-type, store only the summary; details can be separate
  const sql = `
    INSERT INTO bookings
    (trip_type, departure, arrival, departure_date, return_date, mobile_number, email, num_passengers)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  `;

  db.execute(
    sql,
    [trip_type, departure, arrival, departure_date, return_date, mobile_number, email, num_passengers],
    (err, results) => {
      if (err) {
        console.error('Insert error:', err);
        res.status(500).send('Database error');
      } else {
        res.send('Booking saved successfully!');
      }
    }
  );
});

// Serve your HTML form (assumes in same folder)
app.use(express.static(__dirname));

app.listen(3000, () => console.log('Server running on http://localhost:3000'));
