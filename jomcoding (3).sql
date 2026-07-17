-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 14, 2026 at 02:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jomcoding`
--

-- --------------------------------------------------------

--
-- Table structure for table `courses`
--

CREATE TABLE `courses` (
  `course_id` int(11) NOT NULL,
  `course_title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `difficulty_level` enum('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
  `estimated_hours` int(11) DEFAULT NULL,
  `thumbnail_url` varchar(255) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `courses`
--

INSERT INTO `courses` (`course_id`, `course_title`, `description`, `difficulty_level`, `estimated_hours`, `thumbnail_url`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Programming Basics', 'Learn the fundamentals of programming with variables, loops, and conditional statements.', 'Beginner', 10, NULL, 1, '2025-12-23 22:14:18', '2025-12-23 22:14:18'),
(2, 'Web Development', 'Master HTML and CSS to create beautiful and responsive websites.', 'Beginner', 15, NULL, 1, '2025-12-23 22:14:18', '2025-12-23 22:14:18'),
(3, 'JavaScript Essentials', 'Dive into JavaScript and learn to add interactivity to your web applications.', 'Intermediate', 20, NULL, 1, '2025-12-23 22:14:18', '2025-12-23 22:14:18'),
(4, 'PHP Backend Development', 'Build dynamic websites with PHP, handling forms and database operations.', 'Intermediate', 18, NULL, 1, '2025-12-23 22:14:18', '2025-12-23 22:14:18'),
(5, 'MySQL Database', 'Learn to design, query, and manage databases using MySQL.', 'Beginner', 12, NULL, 1, '2025-12-23 22:14:18', '2025-12-23 22:14:18');

-- --------------------------------------------------------

--
-- Table structure for table `exercises`
--

CREATE TABLE `exercises` (
  `exercise_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `exercise_title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `difficulty` enum('Easy','Medium','Hard') DEFAULT 'Easy',
  `sample_input` text DEFAULT NULL,
  `sample_output` text DEFAULT NULL,
  `test_cases` text DEFAULT NULL,
  `points` int(11) DEFAULT 10,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lessons`
--

CREATE TABLE `lessons` (
  `lesson_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_title` varchar(150) NOT NULL,
  `content` text NOT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `duration_minutes` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `lessons`
--

INSERT INTO `lessons` (`lesson_id`, `course_id`, `lesson_title`, `content`, `video_url`, `sort_order`, `duration_minutes`, `created_at`) VALUES
(10, 1, 'Problem Solving Techniques', 'Develop your algorithmic thinking! Learn systematic approaches to solving programming problems. Practice breaking down complex problems into manageable steps.', NULL, 10, 30, '2026-01-26 09:31:50'),
(11, 2, 'Introduction to HTML', 'Start your web development journey! Learn what HTML is and how it structures web pages. Understand the basic syntax and create your first HTML document.', NULL, 1, 20, '2026-01-26 09:31:50'),
(12, 2, 'HTML Elements and Tags', 'Master HTML elements! Learn about headings, paragraphs, links, images, and more. Discover semantic HTML and best practices for structuring content.', NULL, 2, 25, '2026-01-26 09:31:50'),
(13, 2, 'HTML Forms and Inputs', 'Create interactive forms! Learn about form elements, input types, labels, and buttons. Understand how to collect user data through HTML forms.', NULL, 3, 28, '2026-01-26 09:31:50'),
(14, 2, 'Introduction to CSS', 'Make your websites beautiful! Learn CSS basics, selectors, and properties. Discover how to style HTML elements and add colors, fonts, and layouts.', NULL, 4, 22, '2026-01-26 09:31:50'),
(15, 2, 'CSS Box Model', 'Understand the foundation of CSS layout! Master margin, padding, border, and content. Learn how elements are sized and positioned on the page.', NULL, 5, 24, '2026-01-26 09:31:50'),
(16, 2, 'CSS Flexbox', 'Create flexible layouts with Flexbox! Learn this powerful layout system for building responsive designs. Master flex containers and flex items.', NULL, 6, 30, '2026-01-26 09:31:50'),
(17, 2, 'CSS Grid Layout', 'Build complex layouts with CSS Grid! Learn this two-dimensional layout system. Create sophisticated page designs with rows and columns.', NULL, 7, 32, '2026-01-26 09:31:50'),
(18, 2, 'Responsive Web Design', 'Make websites that work everywhere! Learn media queries, mobile-first design, and responsive units. Create sites that adapt to any screen size.', NULL, 8, 28, '2026-01-26 09:31:50'),
(19, 2, 'CSS Animations', 'Bring your websites to life! Learn transitions, keyframe animations, and transforms. Add smooth, engaging motion to your web pages.', NULL, 9, 26, '2026-01-26 09:31:50'),
(20, 2, 'Building a Complete Website', 'Put it all together! Build a complete, responsive website from scratch. Apply all the HTML and CSS skills you\'ve learned in a real project.', NULL, 10, 45, '2026-01-26 09:31:50'),
(21, 3, 'JavaScript Fundamentals', 'Welcome to JavaScript! Learn the basics of this powerful programming language. Understand variables, data types, and basic syntax in JavaScript.', NULL, 1, 25, '2026-01-26 09:31:50'),
(22, 3, 'Functions in JavaScript', 'Master JavaScript functions! Learn function declarations, expressions, arrow functions, and callbacks. Understand scope and closures.', NULL, 2, 28, '2026-01-26 09:31:50'),
(23, 3, 'Working with Arrays', 'Become an array expert! Learn array methods like map, filter, reduce, forEach, and more. Master functional programming concepts.', NULL, 3, 30, '2026-01-26 09:31:50'),
(24, 3, 'Objects and JSON', 'Understand JavaScript objects! Learn object literals, properties, methods, and JSON. Discover how to work with structured data.', NULL, 4, 26, '2026-01-26 09:31:50'),
(25, 3, 'DOM Manipulation', 'Control web pages with JavaScript! Learn to select elements, modify content, change styles, and handle events. Make pages interactive.', NULL, 5, 32, '2026-01-26 09:31:50'),
(34, 4, 'Sessions and Cookies', 'Maintain user state! Learn about PHP sessions and cookies. Implement user authentication and personalization features.', NULL, 6, 30, '2026-01-26 09:31:50'),
(35, 5, 'Introduction to Databases', 'Enter the world of databases! Learn what databases are, why they\'re important, and how they organize data. Understand relational databases.', NULL, 1, 20, '2026-01-26 09:31:50'),
(36, 5, 'Basic SQL Queries', 'Start querying data! Learn SELECT statements, WHERE clauses, and basic filtering. Extract the information you need from databases.', NULL, 2, 25, '2026-01-26 09:31:50'),
(37, 5, 'Inserting and Updating Data', 'Modify database data! Learn INSERT, UPDATE, and DELETE statements. Understand how to add, change, and remove records safely.', NULL, 3, 24, '2026-01-26 09:31:50'),
(38, 5, 'Database Design', 'Design efficient databases! Learn about tables, columns, data types, and relationships. Create well-structured database schemas.', NULL, 4, 28, '2026-01-26 09:31:50'),
(39, 5, 'Joins and Relationships', 'Connect your data! Master INNER JOIN, LEFT JOIN, RIGHT JOIN. Learn to query data across multiple related tables.', NULL, 5, 30, '2026-01-26 09:31:50'),
(40, 5, 'MySQL with PHP', 'Connect PHP to MySQL! Learn to execute queries from PHP, handle results, and use prepared statements for security.', NULL, 6, 32, '2026-01-26 09:31:50');

-- --------------------------------------------------------

--
-- Table structure for table `lesson_progress`
--

CREATE TABLE `lesson_progress` (
  `progress_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `lesson_id` int(11) NOT NULL,
  `completed` tinyint(1) DEFAULT 0,
  `completed_at` datetime DEFAULT NULL,
  `time_spent_minutes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `quizzes`
--

CREATE TABLE `quizzes` (
  `quiz_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `lesson_id` int(11) DEFAULT NULL,
  `question` text NOT NULL,
  `option_a` varchar(255) NOT NULL,
  `option_b` varchar(255) NOT NULL,
  `option_c` varchar(255) NOT NULL,
  `option_d` varchar(255) NOT NULL,
  `correct_answer` enum('A','B','C','D') NOT NULL,
  `difficulty` enum('Easy','Normal','Hard') DEFAULT 'Normal',
  `points` int(11) DEFAULT 10,
  `explanation` text DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quizzes`
--

INSERT INTO `quizzes` (`quiz_id`, `course_id`, `lesson_id`, `question`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `difficulty`, `points`, `explanation`, `created_at`) VALUES
(39, 2, NULL, 'What does HTML stand for?', 'Hyper Text Markup Language', 'Home Tool Markup Language', 'Hyperlinks and Text Markup Language', 'Hyperlinking Text Mark Language', 'A', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(40, 2, NULL, 'What does CSS stand for?', 'Cascading Style Sheets', 'Creative Style Sheets', 'Computer Style Sheets', 'Colorful Style Sheets', 'A', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(41, 2, NULL, 'Which HTML tag is used to define an internal style sheet?', '<css>', '<script>', '<style>', '<link>', 'C', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(42, 2, NULL, 'Which property is used to change the background color in CSS?', 'bgcolor', 'color', 'background-color', 'back-color', 'C', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(43, 2, NULL, 'Which HTML attribute is used to define inline styles?', 'class', 'style', 'styles', 'font', 'B', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(44, 2, NULL, 'Which is the correct CSS syntax?', 'body {color: black;}', '{body;color:black;}', 'body:color=black;', '{body:color=black;}', 'A', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(45, 2, NULL, 'How do you insert a comment in a CSS file?', '// this is a comment', '/* this is a comment */', '<!-- this is a comment -->', '// this is a comment //', 'B', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(46, 2, NULL, 'Which HTML tag is used to create a hyperlink?', '<link>', '<a>', '<href>', '<hyperlink>', 'B', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(47, 2, NULL, 'How do you make text bold in HTML?', '<bold>', '<b>', '<strong>', 'Both B and C', 'D', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(48, 2, NULL, 'Which CSS property controls the text size?', 'font-style', 'text-size', 'font-size', 'text-style', 'C', 'Normal', 13, NULL, '2026-02-03 16:15:12'),
(49, 1, NULL, 'What is a variable in programming?', 'A fixed value that cannot change', 'A container for storing data values', 'A type of loop', 'A function parameter', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(50, 1, NULL, 'Which of the following is NOT a primitive data type?', 'int', 'float', 'string', 'array', 'D', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(51, 1, NULL, 'What does the \"if\" statement do?', 'Repeats code multiple times', 'Defines a function', 'Makes decisions based on conditions', 'Stores data', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(52, 1, NULL, 'What is a loop used for?', 'To store data', 'To repeat code multiple times', 'To define variables', 'To create functions', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(53, 1, NULL, 'Which operator is used for assignment in most programming languages?', '+', '==', '=', '!=', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(54, 1, NULL, 'What is the purpose of a function?', 'To store data', 'To repeat code', 'To organize reusable code', 'To compare values', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(55, 1, NULL, 'What does \"debugging\" mean?', 'Writing new code', 'Finding and fixing errors', 'Running the program', 'Compiling code', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(56, 1, NULL, 'What is an array?', 'A single value', 'A collection of values', 'A type of loop', 'A conditional statement', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(57, 1, NULL, 'Which of these is a comparison operator?', '=', '==', '===', 'Both B and C', 'D', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(58, 1, NULL, 'What does \"syntax\" refer to in programming?', 'The meaning of code', 'The rules for writing code', 'The speed of execution', 'The memory usage', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(59, 1, NULL, 'What is a boolean value?', 'A number', 'A string', 'True or False', 'An array', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(60, 1, NULL, 'What does the \"else\" statement do?', 'Repeats code', 'Runs when the if condition is false', 'Defines a variable', 'Ends a program', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(61, 1, NULL, 'What is concatenation?', 'Combining strings together', 'Dividing numbers', 'Comparing values', 'Deleting data', 'A', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(62, 1, NULL, 'What is the purpose of comments in code?', 'To make code run faster', 'To explain what code does', 'To store data', 'To create loops', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(63, 1, NULL, 'What does \"iteration\" mean?', 'A single execution', 'Repeating a process', 'Defining variables', 'Creating functions', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(64, 1, NULL, 'Which of these is a logical operator?', '+', '-', 'AND', '=', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(65, 1, NULL, 'What is the difference between \"=\" and \"==\"?', 'There is no difference', '\"=\" assigns, \"==\" compares', '\"=\" compares, \"==\" assigns', 'Both assign values', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(66, 1, NULL, 'What is a parameter in a function?', 'The return value', 'The function name', 'Input passed to the function', 'The function body', 'C', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(67, 1, NULL, 'What does \"return\" do in a function?', 'Stops the program', 'Sends a value back', 'Creates a loop', 'Defines a variable', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(68, 1, NULL, 'What is an algorithm?', 'A programming language', 'A step-by-step procedure', 'A type of variable', 'An error message', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(69, 1, NULL, 'What does \"scope\" refer to?', 'Code speed', 'Variable accessibility', 'Loop iterations', 'Function names', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(70, 1, NULL, 'What is the purpose of indentation?', 'To make code look nice', 'To show code structure', 'To make code run faster', 'To save memory', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(71, 1, NULL, 'What is a string?', 'A number', 'Text data', 'A boolean', 'A loop', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(72, 1, NULL, 'What does \"null\" represent?', 'Zero', 'Empty or no value', 'True', 'False', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(73, 1, NULL, 'What is the purpose of the \"break\" statement?', 'To pause execution', 'To exit a loop early', 'To define a variable', 'To call a function', 'B', 'Normal', 13, NULL, '2026-02-03 16:45:50'),
(74, 3, NULL, 'What does PHP stand for?', 'Personal Home Page', 'PHP: Hypertext Preprocessor', 'Private HTML Pages', 'Public Hypertext Program', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(75, 3, NULL, 'Which symbol starts a PHP variable?', '@', '#', '$', '%', 'C', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(76, 3, NULL, 'How do you end a PHP statement?', 'Period (.)', 'Semicolon (;)', 'Comma (,)', 'Colon (:)', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(77, 3, NULL, 'Which tag is used to start PHP code?', '<php>', '<?php', '{php}', '[php]', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(78, 3, NULL, 'How do you write a comment in PHP?', '// This is a comment', '<!-- This is a comment -->', '# This is a comment', '** This is a comment', 'A', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(79, 3, NULL, 'Which function displays text in PHP?', 'print()', 'echo', 'display()', 'show()', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(80, 3, NULL, 'What is the correct way to create a variable named \"name\"?', 'var name', '$name', 'name$', 'variable name', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(81, 3, NULL, 'Which is the correct way to check if two values are equal?', 'a = b', 'a == b', 'a === b', 'Both B and C', 'D', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(82, 3, NULL, 'How do you start a PHP session?', 'start_session()', 'session_start()', 'begin_session()', 'session.start()', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(83, 3, NULL, 'Which superglobal gets form data from POST method?', '$_GET', '$_POST', '$_FORM', '$_DATA', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(84, 3, NULL, 'What does \"echo\" do in PHP?', 'Gets user input', 'Outputs text', 'Creates variables', 'Starts a loop', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(85, 3, NULL, 'How do you create a function in PHP?', 'function myFunction()', 'def myFunction()', 'create myFunction()', 'func myFunction()', 'A', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(86, 3, NULL, 'Which is used to connect PHP and MySQL?', 'link()', 'mysqli_connect()', 'database()', 'mysql_open()', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(87, 3, NULL, 'What does if...else do in PHP?', 'Repeats code', 'Makes decisions', 'Creates variables', 'Connects database', 'B', 'Normal', 22, NULL, '2026-02-03 20:38:05'),
(88, 3, NULL, 'How do you include another PHP file?', 'import \"file.php\"', 'add \"file.php\"', 'include \"file.php\"', 'load \"file.php\"', 'C', 'Normal', 21, NULL, '2026-02-03 20:38:05'),
(89, 4, NULL, 'What does JavaScript primarily run on?', 'Server only', 'Web browsers', 'Mobile apps only', 'Desktop applications only', 'B', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(90, 4, NULL, 'Which keyword is used to declare a variable in modern JavaScript?', 'var', 'let', 'int', 'variable', 'B', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(91, 4, NULL, 'What data type is the value \"Hello World\"?', 'Number', 'Boolean', 'String', 'Object', 'C', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(92, 4, NULL, 'Which method is used to print output to the console?', 'console.log()', 'print()', 'echo()', 'display()', 'A', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(93, 4, NULL, 'How do you define a function in JavaScript?', 'function myFunction() {}', 'def myFunction() {}', 'func myFunction() {}', 'define myFunction() {}', 'A', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(94, 4, NULL, 'What is the result of 5 + 3 in JavaScript?', '53', '8', '15', 'Error', 'B', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(95, 4, NULL, 'What are the two boolean values in JavaScript?', 'yes and no', 'true and false', '1 and 0', 'on and off', 'B', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(96, 4, NULL, 'How do you write a single-line comment in JavaScript?', '/* comment */', '<!-- comment -->', '// comment', '# comment', 'C', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(97, 4, NULL, 'How do you create an array in JavaScript?', 'let arr = []', 'let arr = ()', 'let arr = {}', 'let arr = <>', 'A', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(98, 4, NULL, 'Which statement is used to make decisions in JavaScript?', 'choose', 'if', 'select', 'switch-case', 'B', 'Normal', 10, NULL, '2026-02-04 15:28:11'),
(99, 5, NULL, 'Which SQL command is used to retrieve data from a database?', 'GET', 'SELECT', 'RETRIEVE', 'FETCH', 'B', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(100, 5, NULL, 'Which keyword is used to filter records in SQL?', 'FILTER', 'CONDITION', 'WHERE', 'IF', 'C', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(101, 5, NULL, 'Which SQL command is used to add new data to a table?', 'ADD', 'INSERT INTO', 'CREATE', 'PUT', 'B', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(102, 5, NULL, 'Which SQL command is used to modify existing data in a table?', 'MODIFY', 'CHANGE', 'UPDATE', 'ALTER', 'C', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(103, 5, NULL, 'Which SQL command is used to remove data from a table?', 'REMOVE', 'DROP', 'DELETE', 'ERASE', 'C', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(104, 5, NULL, 'Which SQL command creates a new database?', 'NEW DATABASE', 'CREATE DATABASE', 'MAKE DATABASE', 'ADD DATABASE', 'B', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(105, 5, NULL, 'What is a PRIMARY KEY in a database table?', 'The first column in a table', 'A unique identifier for each record', 'A password for the database', 'The table name', 'B', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(106, 5, NULL, 'Which data type is used to store text in MySQL?', 'INT', 'VARCHAR', 'DATE', 'BOOLEAN', 'B', 'Normal', 1, NULL, '2026-02-04 19:12:26'),
(107, 5, NULL, 'Which keyword is used to sort the result in SQL?', 'SORT BY', 'ORDER BY', 'ARRANGE BY', 'GROUP BY', 'B', 'Normal', 1, NULL, '2026-02-04 19:12:26'),
(108, 5, NULL, 'Which SQL keyword limits the number of rows returned?', 'TOP', 'LIMIT', 'MAX', 'COUNT', 'B', 'Normal', 1, NULL, '2026-02-04 19:12:26'),
(109, 5, NULL, 'Which operator is used to search for a pattern in SQL?', 'SEARCH', 'FIND', 'LIKE', 'MATCH', 'C', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(110, 5, NULL, 'Which function returns the number of rows in a table?', 'TOTAL()', 'SUM()', 'COUNT()', 'NUMBER()', 'C', 'Normal', 2, NULL, '2026-02-04 19:12:26'),
(111, 5, NULL, 'Which function calculates the average of a numeric column?', 'MEAN()', 'AVG()', 'AVERAGE()', 'CALC()', 'B', 'Normal', 1, NULL, '2026-02-04 19:12:26'),
(112, 5, NULL, 'Which JOIN returns only matching records from both tables?', 'LEFT JOIN', 'RIGHT JOIN', 'INNER JOIN', 'OUTER JOIN', 'C', 'Normal', 1, NULL, '2026-02-04 19:12:26'),
(113, 5, NULL, 'What does NULL represent in a database?', 'Zero value', 'Empty string', 'Missing or unknown value', 'False value', 'C', 'Normal', 1, NULL, '2026-02-04 19:12:26');

-- --------------------------------------------------------

--
-- Table structure for table `quiz_attempts`
--

CREATE TABLE `quiz_attempts` (
  `attempt_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `quiz_id` int(11) NOT NULL,
  `selected_answer` enum('A','B','C','D') NOT NULL,
  `is_correct` tinyint(1) NOT NULL,
  `points_earned` int(11) DEFAULT 0,
  `attempted_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `quiz_attempts`
--

INSERT INTO `quiz_attempts` (`attempt_id`, `student_id`, `quiz_id`, `selected_answer`, `is_correct`, `points_earned`, `attempted_at`) VALUES
(308, 10, 39, 'A', 1, 13, '2026-02-03 16:16:20'),
(309, 10, 40, 'A', 1, 13, '2026-02-03 16:16:20'),
(310, 10, 41, 'A', 0, 0, '2026-02-03 16:16:20'),
(311, 10, 42, 'C', 1, 13, '2026-02-03 16:16:20'),
(312, 10, 43, 'C', 0, 0, '2026-02-03 16:16:20'),
(313, 10, 44, 'A', 1, 13, '2026-02-03 16:16:20'),
(314, 10, 45, 'A', 0, 0, '2026-02-03 16:16:20'),
(315, 10, 46, 'A', 0, 0, '2026-02-03 16:16:20'),
(316, 10, 47, 'B', 0, 0, '2026-02-03 16:16:20'),
(317, 10, 48, 'B', 0, 0, '2026-02-03 16:16:20'),
(318, 10, 49, 'A', 0, 0, '2026-02-03 16:50:10'),
(319, 10, 50, 'A', 0, 0, '2026-02-03 16:50:10'),
(320, 10, 51, 'A', 0, 0, '2026-02-03 16:50:10'),
(321, 10, 52, 'A', 0, 0, '2026-02-03 16:50:10'),
(322, 10, 53, 'A', 0, 0, '2026-02-03 16:50:10'),
(323, 10, 54, 'A', 0, 0, '2026-02-03 16:50:10'),
(324, 10, 55, 'A', 0, 0, '2026-02-03 16:50:10'),
(325, 10, 56, 'A', 0, 0, '2026-02-03 16:50:10'),
(326, 10, 57, 'A', 0, 0, '2026-02-03 16:50:10'),
(327, 10, 58, 'A', 0, 0, '2026-02-03 16:50:10'),
(328, 10, 59, 'A', 0, 0, '2026-02-03 16:50:10'),
(329, 10, 60, 'A', 0, 0, '2026-02-03 16:50:10'),
(330, 10, 61, 'A', 1, 13, '2026-02-03 16:50:10'),
(331, 10, 62, 'A', 0, 0, '2026-02-03 16:50:10'),
(332, 10, 63, 'C', 0, 0, '2026-02-03 16:50:10'),
(333, 10, 64, 'A', 0, 0, '2026-02-03 16:50:10'),
(334, 10, 65, 'A', 0, 0, '2026-02-03 16:50:11'),
(335, 10, 66, 'A', 0, 0, '2026-02-03 16:50:11'),
(336, 10, 67, 'A', 0, 0, '2026-02-03 16:50:11'),
(337, 10, 68, 'A', 0, 0, '2026-02-03 16:50:11'),
(338, 10, 69, 'A', 0, 0, '2026-02-03 16:50:11'),
(339, 10, 70, 'A', 0, 0, '2026-02-03 16:50:11'),
(340, 10, 71, 'A', 0, 0, '2026-02-03 16:50:11'),
(341, 10, 72, 'A', 0, 0, '2026-02-03 16:50:11'),
(342, 10, 73, 'A', 0, 0, '2026-02-03 16:50:11'),
(343, 10, 74, 'A', 0, 0, '2026-02-03 20:38:51'),
(344, 10, 75, 'C', 1, 22, '2026-02-03 20:38:51'),
(345, 10, 76, 'A', 0, 0, '2026-02-03 20:38:51'),
(346, 10, 77, 'A', 0, 0, '2026-02-03 20:38:51'),
(347, 10, 78, 'A', 1, 22, '2026-02-03 20:38:51'),
(348, 10, 79, 'A', 0, 0, '2026-02-03 20:38:51'),
(349, 10, 80, 'A', 0, 0, '2026-02-03 20:38:51'),
(350, 10, 81, 'A', 0, 0, '2026-02-03 20:38:51'),
(351, 10, 82, 'A', 0, 0, '2026-02-03 20:38:51'),
(352, 10, 83, 'A', 0, 0, '2026-02-03 20:38:51'),
(353, 10, 84, 'A', 0, 0, '2026-02-03 20:38:51'),
(354, 10, 85, 'A', 1, 22, '2026-02-03 20:38:51'),
(355, 10, 86, 'A', 0, 0, '2026-02-03 20:38:51'),
(356, 10, 87, 'A', 0, 0, '2026-02-03 20:38:51'),
(357, 10, 88, 'A', 0, 0, '2026-02-03 20:38:51'),
(358, 10, 49, 'A', 0, 0, '2026-02-03 20:40:17'),
(359, 10, 50, 'A', 0, 0, '2026-02-03 20:40:17'),
(360, 10, 51, 'A', 0, 0, '2026-02-03 20:40:17'),
(361, 10, 52, 'A', 0, 0, '2026-02-03 20:40:17'),
(362, 10, 53, 'A', 0, 0, '2026-02-03 20:40:17'),
(363, 10, 54, 'A', 0, 0, '2026-02-03 20:40:17'),
(364, 10, 55, 'A', 0, 0, '2026-02-03 20:40:17'),
(365, 10, 56, 'A', 0, 0, '2026-02-03 20:40:17'),
(366, 10, 57, 'A', 0, 0, '2026-02-03 20:40:17'),
(367, 10, 58, 'A', 0, 0, '2026-02-03 20:40:17'),
(368, 10, 59, 'A', 0, 0, '2026-02-03 20:40:17'),
(369, 10, 60, 'A', 0, 0, '2026-02-03 20:40:17'),
(370, 10, 61, 'A', 1, 13, '2026-02-03 20:40:17'),
(371, 10, 62, 'A', 0, 0, '2026-02-03 20:40:17'),
(372, 10, 63, 'A', 0, 0, '2026-02-03 20:40:17'),
(373, 10, 64, 'A', 0, 0, '2026-02-03 20:40:17'),
(374, 10, 65, 'A', 0, 0, '2026-02-03 20:40:17'),
(375, 10, 66, 'A', 0, 0, '2026-02-03 20:40:17'),
(376, 10, 67, 'A', 0, 0, '2026-02-03 20:40:17'),
(377, 10, 68, 'A', 0, 0, '2026-02-03 20:40:17'),
(378, 10, 69, 'A', 0, 0, '2026-02-03 20:40:17'),
(379, 10, 70, 'A', 0, 0, '2026-02-03 20:40:17'),
(380, 10, 71, 'A', 0, 0, '2026-02-03 20:40:17'),
(381, 10, 72, 'A', 0, 0, '2026-02-03 20:40:17'),
(382, 10, 73, 'A', 0, 0, '2026-02-03 20:40:17'),
(383, 10, 39, 'A', 1, 13, '2026-02-03 20:47:13'),
(384, 10, 40, 'A', 1, 13, '2026-02-03 20:47:13'),
(385, 10, 41, 'A', 0, 0, '2026-02-03 20:47:13'),
(386, 10, 42, 'C', 1, 13, '2026-02-03 20:47:13'),
(387, 10, 43, 'C', 0, 0, '2026-02-03 20:47:13'),
(388, 10, 44, 'A', 1, 13, '2026-02-03 20:47:13'),
(389, 10, 45, 'D', 0, 0, '2026-02-03 20:47:13'),
(390, 10, 46, 'A', 0, 0, '2026-02-03 20:47:13'),
(391, 10, 47, 'B', 0, 0, '2026-02-03 20:47:13'),
(392, 10, 48, 'C', 1, 13, '2026-02-03 20:47:13'),
(393, 10, 74, 'B', 1, 22, '2026-02-03 22:08:11'),
(394, 10, 75, 'C', 1, 22, '2026-02-03 22:08:11'),
(395, 10, 76, 'B', 1, 22, '2026-02-03 22:08:11'),
(396, 10, 77, 'B', 1, 22, '2026-02-03 22:08:11'),
(397, 10, 78, 'A', 1, 22, '2026-02-03 22:08:11'),
(398, 10, 79, 'B', 1, 22, '2026-02-03 22:08:11'),
(399, 10, 80, 'B', 1, 22, '2026-02-03 22:08:11'),
(400, 10, 81, 'B', 0, 0, '2026-02-03 22:08:11'),
(401, 10, 82, 'A', 0, 0, '2026-02-03 22:08:11'),
(402, 10, 83, 'B', 1, 22, '2026-02-03 22:08:11'),
(403, 10, 84, 'B', 1, 22, '2026-02-03 22:08:11'),
(404, 10, 85, 'A', 1, 22, '2026-02-03 22:08:11'),
(405, 10, 86, 'B', 1, 22, '2026-02-03 22:08:11'),
(406, 10, 87, 'B', 1, 22, '2026-02-03 22:08:11'),
(407, 10, 88, 'A', 0, 0, '2026-02-03 22:08:11'),
(408, 10, 89, 'B', 1, 10, '2026-02-04 15:29:13'),
(409, 10, 90, 'B', 1, 10, '2026-02-04 15:29:13'),
(410, 10, 91, 'C', 1, 10, '2026-02-04 15:29:13'),
(411, 10, 92, 'A', 1, 10, '2026-02-04 15:29:13'),
(412, 10, 93, 'A', 1, 10, '2026-02-04 15:29:13'),
(413, 10, 94, 'B', 1, 10, '2026-02-04 15:29:13'),
(414, 10, 95, 'B', 1, 10, '2026-02-04 15:29:13'),
(415, 10, 96, 'C', 1, 10, '2026-02-04 15:29:13'),
(416, 10, 97, 'A', 1, 10, '2026-02-04 15:29:13'),
(417, 10, 98, 'B', 1, 10, '2026-02-04 15:29:13'),
(418, 10, 99, 'B', 1, 2, '2026-02-04 19:15:01'),
(419, 10, 100, 'C', 1, 2, '2026-02-04 19:15:01'),
(420, 10, 101, 'B', 1, 2, '2026-02-04 19:15:01'),
(421, 10, 102, 'C', 1, 2, '2026-02-04 19:15:01'),
(422, 10, 103, 'C', 1, 2, '2026-02-04 19:15:01'),
(423, 10, 104, 'B', 1, 2, '2026-02-04 19:15:01'),
(424, 10, 105, 'B', 1, 2, '2026-02-04 19:15:01'),
(425, 10, 106, 'B', 1, 1, '2026-02-04 19:15:01'),
(426, 10, 107, 'B', 1, 1, '2026-02-04 19:15:01'),
(427, 10, 108, 'B', 1, 1, '2026-02-04 19:15:01'),
(428, 10, 109, 'C', 1, 2, '2026-02-04 19:15:01'),
(429, 10, 110, 'C', 1, 2, '2026-02-04 19:15:01'),
(430, 10, 111, 'C', 0, 0, '2026-02-04 19:15:01'),
(431, 10, 112, 'A', 0, 0, '2026-02-04 19:15:01'),
(432, 10, 113, 'C', 1, 1, '2026-02-04 19:15:01'),
(433, 14, 49, 'B', 1, 13, '2026-02-05 08:36:39'),
(434, 14, 50, 'D', 1, 13, '2026-02-05 08:36:39'),
(435, 14, 51, 'C', 1, 13, '2026-02-05 08:36:39'),
(436, 14, 52, 'B', 1, 13, '2026-02-05 08:36:39'),
(437, 14, 53, 'C', 1, 13, '2026-02-05 08:36:39'),
(438, 14, 54, 'C', 1, 13, '2026-02-05 08:36:39'),
(439, 14, 55, 'B', 1, 13, '2026-02-05 08:36:39'),
(440, 14, 56, 'B', 1, 13, '2026-02-05 08:36:39'),
(441, 14, 57, 'D', 1, 13, '2026-02-05 08:36:39'),
(442, 14, 58, 'B', 1, 13, '2026-02-05 08:36:39'),
(443, 14, 59, 'C', 1, 13, '2026-02-05 08:36:39'),
(444, 14, 60, 'B', 1, 13, '2026-02-05 08:36:39'),
(445, 14, 61, 'A', 1, 13, '2026-02-05 08:36:39'),
(446, 14, 62, 'B', 1, 13, '2026-02-05 08:36:39'),
(447, 14, 63, 'B', 1, 13, '2026-02-05 08:36:39'),
(448, 14, 64, 'C', 1, 13, '2026-02-05 08:36:39'),
(449, 14, 65, 'B', 1, 13, '2026-02-05 08:36:39'),
(450, 14, 66, 'C', 1, 13, '2026-02-05 08:36:39'),
(451, 14, 67, 'B', 1, 13, '2026-02-05 08:36:39'),
(452, 14, 68, 'B', 1, 13, '2026-02-05 08:36:39'),
(453, 14, 69, 'B', 1, 13, '2026-02-05 08:36:39'),
(454, 14, 70, 'B', 1, 13, '2026-02-05 08:36:39'),
(455, 14, 71, 'A', 0, 0, '2026-02-05 08:36:39'),
(456, 14, 72, 'B', 1, 13, '2026-02-05 08:36:39'),
(457, 14, 73, 'B', 1, 13, '2026-02-05 08:36:39'),
(458, 16, 49, 'A', 0, 0, '2026-02-07 22:13:25'),
(459, 16, 50, 'A', 0, 0, '2026-02-07 22:13:25'),
(460, 16, 51, 'A', 0, 0, '2026-02-07 22:13:25'),
(461, 16, 52, 'A', 0, 0, '2026-02-07 22:13:25'),
(462, 16, 53, 'B', 0, 0, '2026-02-07 22:13:25'),
(463, 16, 54, 'B', 0, 0, '2026-02-07 22:13:25'),
(464, 16, 55, 'B', 1, 13, '2026-02-07 22:13:25'),
(465, 16, 56, 'B', 1, 13, '2026-02-07 22:13:25'),
(466, 16, 57, 'B', 0, 0, '2026-02-07 22:13:25'),
(467, 16, 58, 'B', 1, 13, '2026-02-07 22:13:25'),
(468, 16, 59, 'B', 0, 0, '2026-02-07 22:13:25'),
(469, 16, 60, 'B', 1, 13, '2026-02-07 22:13:25'),
(470, 16, 61, 'B', 0, 0, '2026-02-07 22:13:25'),
(471, 16, 62, 'B', 1, 13, '2026-02-07 22:13:25'),
(472, 16, 63, 'B', 1, 13, '2026-02-07 22:13:25'),
(473, 16, 64, 'B', 0, 0, '2026-02-07 22:13:25'),
(474, 16, 65, 'B', 1, 13, '2026-02-07 22:13:25'),
(475, 16, 66, 'B', 0, 0, '2026-02-07 22:13:25'),
(476, 16, 67, 'B', 1, 13, '2026-02-07 22:13:25'),
(477, 16, 68, 'B', 1, 13, '2026-02-07 22:13:25'),
(478, 16, 69, 'B', 1, 13, '2026-02-07 22:13:25'),
(479, 16, 70, 'B', 1, 13, '2026-02-07 22:13:25'),
(480, 16, 71, 'B', 1, 13, '2026-02-07 22:13:25'),
(481, 16, 72, 'B', 1, 13, '2026-02-07 22:13:25'),
(482, 16, 73, 'B', 1, 13, '2026-02-07 22:13:25'),
(483, 16, 39, 'A', 1, 13, '2026-02-07 22:14:27'),
(484, 16, 40, 'A', 1, 13, '2026-02-07 22:14:27'),
(485, 16, 41, 'C', 1, 13, '2026-02-07 22:14:27'),
(486, 16, 42, 'C', 1, 13, '2026-02-07 22:14:27'),
(487, 16, 43, 'B', 1, 13, '2026-02-07 22:14:27'),
(488, 16, 44, 'A', 1, 13, '2026-02-07 22:14:27'),
(489, 16, 45, 'B', 1, 13, '2026-02-07 22:14:27'),
(490, 16, 46, 'D', 0, 0, '2026-02-07 22:14:27'),
(491, 16, 47, 'C', 0, 0, '2026-02-07 22:14:27'),
(492, 16, 48, 'B', 0, 0, '2026-02-07 22:14:27'),
(493, 16, 89, 'B', 1, 10, '2026-02-07 22:15:55'),
(494, 16, 90, 'B', 1, 10, '2026-02-07 22:15:55'),
(495, 16, 91, 'C', 1, 10, '2026-02-07 22:15:55'),
(496, 16, 92, 'A', 1, 10, '2026-02-07 22:15:55'),
(497, 16, 93, 'A', 1, 10, '2026-02-07 22:15:55'),
(498, 16, 94, 'B', 1, 10, '2026-02-07 22:15:55'),
(499, 16, 95, 'B', 1, 10, '2026-02-07 22:15:55'),
(500, 16, 96, 'D', 0, 0, '2026-02-07 22:15:55'),
(501, 16, 97, 'B', 0, 0, '2026-02-07 22:15:55'),
(502, 16, 98, 'B', 1, 10, '2026-02-07 22:15:55'),
(503, 16, 74, 'B', 1, 22, '2026-02-07 22:16:49'),
(504, 16, 75, 'C', 1, 22, '2026-02-07 22:16:49'),
(505, 16, 76, 'B', 1, 22, '2026-02-07 22:16:49'),
(506, 16, 77, 'B', 1, 22, '2026-02-07 22:16:49'),
(507, 16, 78, 'A', 1, 22, '2026-02-07 22:16:49'),
(508, 16, 79, 'B', 1, 22, '2026-02-07 22:16:49'),
(509, 16, 80, 'B', 1, 22, '2026-02-07 22:16:49'),
(510, 16, 81, 'D', 1, 22, '2026-02-07 22:16:49'),
(511, 16, 82, 'B', 1, 22, '2026-02-07 22:16:49'),
(512, 16, 83, 'B', 1, 22, '2026-02-07 22:16:49'),
(513, 16, 84, 'B', 1, 22, '2026-02-07 22:16:49'),
(514, 16, 85, 'A', 1, 22, '2026-02-07 22:16:49'),
(515, 16, 86, 'B', 1, 22, '2026-02-07 22:16:49'),
(516, 16, 87, 'B', 1, 22, '2026-02-07 22:16:49'),
(517, 16, 88, 'C', 1, 21, '2026-02-07 22:16:49'),
(518, 16, 99, 'B', 1, 2, '2026-02-07 22:17:45'),
(519, 16, 100, 'C', 1, 2, '2026-02-07 22:17:45'),
(520, 16, 101, 'B', 1, 2, '2026-02-07 22:17:45'),
(521, 16, 102, 'C', 1, 2, '2026-02-07 22:17:45'),
(522, 16, 103, 'C', 1, 2, '2026-02-07 22:17:45'),
(523, 16, 104, 'B', 1, 2, '2026-02-07 22:17:45'),
(524, 16, 105, 'A', 0, 0, '2026-02-07 22:17:45'),
(525, 16, 106, 'B', 1, 1, '2026-02-07 22:17:45'),
(526, 16, 107, 'B', 1, 1, '2026-02-07 22:17:45'),
(527, 16, 108, 'B', 1, 1, '2026-02-07 22:17:45'),
(528, 16, 109, 'B', 0, 0, '2026-02-07 22:17:45'),
(529, 16, 110, 'B', 0, 0, '2026-02-07 22:17:45'),
(530, 16, 111, 'C', 0, 0, '2026-02-07 22:17:45'),
(531, 16, 112, 'B', 0, 0, '2026-02-07 22:17:45'),
(532, 16, 113, 'B', 0, 0, '2026-02-07 22:17:45'),
(533, 18, 49, 'B', 1, 13, '2026-02-08 20:35:22'),
(534, 18, 50, 'D', 1, 13, '2026-02-08 20:35:22'),
(535, 18, 51, 'C', 1, 13, '2026-02-08 20:35:22'),
(536, 18, 52, 'B', 1, 13, '2026-02-08 20:35:22'),
(537, 18, 53, 'C', 1, 13, '2026-02-08 20:35:22'),
(538, 18, 54, 'C', 1, 13, '2026-02-08 20:35:22'),
(539, 18, 55, 'B', 1, 13, '2026-02-08 20:35:22'),
(540, 18, 56, 'B', 1, 13, '2026-02-08 20:35:22'),
(541, 18, 57, 'D', 1, 13, '2026-02-08 20:35:22'),
(542, 18, 58, 'C', 0, 0, '2026-02-08 20:35:22'),
(543, 18, 59, 'B', 0, 0, '2026-02-08 20:35:22'),
(544, 18, 60, 'C', 0, 0, '2026-02-08 20:35:22'),
(545, 18, 61, 'A', 1, 13, '2026-02-08 20:35:22'),
(546, 18, 62, 'B', 1, 13, '2026-02-08 20:35:22'),
(547, 18, 63, 'B', 1, 13, '2026-02-08 20:35:22'),
(548, 18, 64, 'C', 1, 13, '2026-02-08 20:35:22'),
(549, 18, 65, 'B', 1, 13, '2026-02-08 20:35:22'),
(550, 18, 66, 'C', 1, 13, '2026-02-08 20:35:22'),
(551, 18, 67, 'B', 1, 13, '2026-02-08 20:35:22'),
(552, 18, 68, 'B', 1, 13, '2026-02-08 20:35:22'),
(553, 18, 69, 'B', 1, 13, '2026-02-08 20:35:22'),
(578, 20, 49, 'B', 1, 13, '2026-02-09 09:33:19'),
(579, 20, 50, 'B', 0, 0, '2026-02-09 09:33:19'),
(580, 20, 51, 'C', 1, 13, '2026-02-09 09:33:19'),
(581, 20, 52, 'B', 1, 13, '2026-02-09 09:33:19'),
(582, 20, 53, 'C', 1, 13, '2026-02-09 09:33:19'),
(583, 20, 54, 'C', 1, 13, '2026-02-09 09:33:19'),
(584, 20, 55, 'B', 1, 13, '2026-02-09 09:33:19'),
(585, 20, 56, 'B', 1, 13, '2026-02-09 09:33:19'),
(586, 20, 57, 'D', 1, 13, '2026-02-09 09:33:19'),
(587, 20, 58, 'B', 1, 13, '2026-02-09 09:33:19'),
(588, 20, 59, 'C', 1, 13, '2026-02-09 09:33:19'),
(589, 20, 60, 'B', 1, 13, '2026-02-09 09:33:19'),
(590, 20, 61, 'A', 1, 13, '2026-02-09 09:33:19'),
(591, 20, 62, 'B', 1, 13, '2026-02-09 09:33:19'),
(592, 20, 63, 'A', 0, 0, '2026-02-09 09:33:19'),
(593, 20, 64, 'B', 0, 0, '2026-02-09 09:33:19'),
(594, 20, 65, 'B', 1, 13, '2026-02-09 09:33:19'),
(595, 20, 66, 'C', 1, 13, '2026-02-09 09:33:19'),
(596, 20, 67, 'B', 1, 13, '2026-02-09 09:33:19'),
(597, 20, 68, 'C', 0, 0, '2026-02-09 09:33:19'),
(598, 20, 69, 'B', 1, 13, '2026-02-09 09:33:19'),
(599, 20, 70, 'B', 1, 13, '2026-02-09 09:33:19'),
(600, 20, 71, 'B', 1, 13, '2026-02-09 09:33:19'),
(601, 20, 72, 'B', 1, 13, '2026-02-09 09:33:19'),
(602, 20, 73, 'B', 1, 13, '2026-02-09 09:33:19');

-- --------------------------------------------------------

--
-- Table structure for table `submissions`
--

CREATE TABLE `submissions` (
  `submission_id` int(11) NOT NULL,
  `exercise_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `code_submitted` text NOT NULL,
  `language` varchar(50) DEFAULT 'php',
  `is_correct` tinyint(1) NOT NULL DEFAULT 0,
  `score` int(11) NOT NULL DEFAULT 0,
  `execution_time` decimal(10,2) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `submitted_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE `subscriptions` (
  `subscription_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `plan_name` varchar(50) DEFAULT 'Basic',
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `status` enum('active','expired','cancelled') NOT NULL DEFAULT 'active',
  `amount` decimal(8,2) NOT NULL DEFAULT 0.00,
  `payment_method` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subscriptions`
--

INSERT INTO `subscriptions` (`subscription_id`, `student_id`, `plan_name`, `start_date`, `end_date`, `status`, `amount`, `payment_method`, `created_at`) VALUES
(28, 10, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Pending Payment', '2026-02-04 21:42:32'),
(29, 10, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Pending Payment', '2026-02-04 21:45:26'),
(30, 4, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Admin Test', '2026-02-04 21:46:21'),
(31, 10, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Pending Payment', '2026-02-04 21:46:41'),
(32, 11, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Pending Payment', '2026-02-04 21:49:47'),
(33, 4, 'Premium', '2026-02-04', '2026-03-04', 'cancelled', 20.00, 'Pending Payment', '2026-02-05 06:42:04'),
(34, 4, 'Premium', '2026-02-04', '2026-03-04', 'active', 20.00, 'Credit Card (**** 3456)', '2026-02-05 06:53:59'),
(35, 12, 'Premium', '2026-02-05', '2026-03-05', 'cancelled', 20.00, 'Pending Payment', '2026-02-05 07:08:22'),
(36, 12, 'Premium', '2026-02-05', '2026-03-05', 'cancelled', 20.00, 'Pending Payment', '2026-02-05 07:08:52'),
(37, 13, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Credit Card (**** 4234)', '2026-02-05 07:17:34'),
(38, 14, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Credit Card (**** 3213)', '2026-02-05 08:32:30'),
(39, 5, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Admin Test', '2026-02-05 14:27:50'),
(40, 6, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Admin Test', '2026-02-05 14:27:53'),
(41, 7, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Admin Test', '2026-02-05 14:27:55'),
(42, 8, 'Premium', '2026-02-05', '2026-03-05', 'active', 20.00, 'Admin Test', '2026-02-05 14:27:57'),
(43, 15, 'Premium', '2026-02-07', '2026-03-07', 'active', 20.00, 'Admin Update', '2026-02-07 21:57:41'),
(44, 10, 'Premium', '2026-02-07', '2026-03-07', 'active', 20.00, 'Admin Update', '2026-02-07 21:59:40'),
(45, 16, 'Premium', '2026-02-07', '2026-03-07', 'active', 20.00, 'Credit Card (**** 4242)', '2026-02-07 22:12:45'),
(46, 12, 'Premium', '2026-02-07', '2026-03-07', 'active', 20.00, 'Admin Update', '2026-02-07 23:46:17'),
(47, 18, 'Premium', '2026-02-08', '2026-03-08', 'active', 20.00, 'Credit Card (**** 5353)', '2026-02-08 20:30:46'),
(48, 20, 'Premium', '2026-02-09', '2026-03-09', 'active', 20.00, 'Credit Card (**** 4243)', '2026-02-09 09:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `birth_date` date DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `bio` text DEFAULT NULL,
  `profile_completed` tinyint(1) DEFAULT 0,
  `subscription_type` enum('free','premium') NOT NULL DEFAULT 'free',
  `role` enum('admin','student') NOT NULL DEFAULT 'student',
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `username`, `full_name`, `email`, `password_hash`, `birth_date`, `country`, `phone`, `gender`, `bio`, `profile_completed`, `subscription_type`, `role`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'Admin User', 'admin@jomcoding.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, NULL, NULL, NULL, NULL, 1, 'free', 'admin', '2025-12-23 22:14:18', '2026-02-04 21:46:20'),
(4, 'haziqisnkandar00', 'Muhammad Haziq Haziq BIN ISNKANDAR', 'haziqisnkandar00@gmail.com', '$2y$10$zN8HMyF60Gu.1oShmT1exuUWGqRIOiwOaUtDgZ.Ua8KPM3UiTHYDK', '2025-12-10', 'Malaysia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2025-12-23 22:35:09', '2026-02-05 06:53:59'),
(5, 'haziqisnkandar20', 'VELAYUDARAJA A/L ADMALINGAM', 'haziqisnkandar20@gmail.com', '$2y$10$Hp6IxM0eq4kZ2oPv3I2pCu8OFnN8ksaZJTgz2DtDc1PQY24wZYz/S', '2025-12-02', 'Malaysia', '+60355420812', 'Male', '', 1, 'premium', 'student', '2025-12-23 22:38:12', '2026-02-05 14:27:50'),
(6, 'azlinahmad03', 'Muhammad Haziq Isnkandar', 'azlinahmad03@gmail.com', '$2y$10$Njgdo7lCsiEMQ5TaqM/Sm.e21rdKl1vM7V3UUPXDyleTATy6XVEGm', '2025-12-02', 'Malaysia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2025-12-23 22:50:15', '2026-02-05 14:27:53'),
(7, 'haziqisnkandar', 'Haziq Muhammad Haziq Bin Isnkandar', 'haziqisnkandar@gmail.com', '$2y$10$9qgnNxpFztfb19tHfpT4bOH.FkJrSIHO4sO/X3L1s2iiCfPFWgG7C', '2025-12-17', 'Malaysia', '+60173820034', 'Other', 'wadwa', 1, 'premium', 'student', '2025-12-24 09:30:26', '2026-02-05 14:27:55'),
(8, 'haziqisnkandar89', 'MUHD HAZIQ', 'haziqisnkandar89@gmail.com', '$2y$10$GNPkYngjtmxSN5/6Xh9ah.nxSXzQXvsJYoYZod7OtqCi3LpFLlchm', '2003-01-24', 'Malaysia', '0179234721', 'Male', '', 1, 'premium', 'student', '2025-12-24 10:20:42', '2026-02-05 14:27:57'),
(9, 'haziqisndr', 'MUHD HAZIQ', 'haziqisndr@gmail.com', '$2y$10$qq.ohmgWwGXoCI2xKPXdGuXubiT/O4oWhe4ghEkrd4T4IPdJx1bd2', '2002-01-24', 'Thailand', '0179234721', 'Male', '', 1, 'free', 'student', '2025-12-24 10:26:26', '2025-12-24 10:26:44'),
(10, 'masam', 'Masam Albana', 'masam@gmail.com', '$2y$10$WUJ.pufbGrl0c0/VYTVCeugqBr7LYoUzoQT1lPnTARxQ./WsUZXnW', '2026-02-02', 'Malaysia', '+60173820034', 'Male', 'adad', 1, 'premium', 'student', '2026-02-03 09:46:26', '2026-02-07 21:59:40'),
(11, 'haziq', 'HAZIQ', 'haziq@live.com', '$2y$10$ktPSGQzSwhagSV0Ysi8h9OcguXTFgx01mXxv0dblO1s1ZkT1lfdDW', '2026-02-01', 'Malaysia', '+60173820034', 'Male', 'AWDWAD', 1, 'free', 'student', '2026-02-04 21:49:22', '2026-02-04 21:50:20'),
(12, 'muhdhaziq', 'Faizal Hussein', 'muhdhaziq@gmail.com', '$2y$10$kJ9SRD30EUW5Ti4i5qiR.u0I7BHQfIV2M6MmSChgaCm.RbiR3I1K2', '2026-02-01', 'Australia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2026-02-05 07:07:19', '2026-02-07 23:46:17'),
(13, 'kudiq', 'Alex Hunter', 'kudiq@gmail.com', '$2y$10$prhMf8xLuVbdBU8iclV8ke.iIj7a5wPpgXeRXNvdOd/kGEhI..DvS', '2026-02-01', 'Malaysia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2026-02-05 07:16:36', '2026-02-05 07:17:34'),
(14, 'gggg', 'Kudiq', 'gggg@gmail.com', '$2y$10$R/.SqnXnR.MU.Gaw0bbV7O2bWEH7ytgu0TanbnpFTfADoaqfQ2B8y', '2026-01-14', 'Malaysia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2026-02-05 07:19:40', '2026-02-05 08:32:30'),
(15, 'haziq12', 'Syafiq Rahim', 'haziq12@gmail.com', '$2y$10$/TJOrnCuOJmLc7uY5a1jQeYfH1IxBxwbYwBiJ6Nc33m6KAOabVSp2', '2016-01-05', 'Malaysia', '+60173820034', 'Male', '', 1, 'premium', 'student', '2026-02-05 13:58:22', '2026-02-07 21:57:41'),
(16, 'isnkandar', 'MUHD HAZIQ BIN ISNKANDAR', 'isnkandar@gmail.com', '$2y$10$6Pg04ScbewIwWhOd0dOEreoJyGfQSX8uXjC/V2.0Ui8oOzdiXVZFy', '2026-02-01', 'Malaysia', '0173820034', 'Male', 'wdawdad', 1, 'premium', 'student', '2026-02-07 22:10:34', '2026-02-07 22:12:45'),
(17, 'nadia', 'Nadia', 'nadia@gmail.com', '$2y$10$A5eySAHC1ilpVAICy.eGO.UvG6ilSWp0ormeY4NO0k7NrMjvPIwQK', '1999-03-02', 'Malaysia', '+60173820034', 'Female', 'Hi, my name is Nadia', 1, 'free', 'student', '2026-02-08 20:08:36', '2026-02-08 20:09:58'),
(18, 'nasiha', 'Nasiha Binti Isnkandar', 'nasiha@gmail.com', '$2y$10$cn19mC1WPFLrPV3O/pTX3eS5iyxcl85WxCCTzPGrHMGEMOzCzTRj.', '2002-01-09', 'Malaysia', '+60173820034', 'Female', '', 1, 'premium', 'student', '2026-02-08 20:22:26', '2026-02-08 20:30:46'),
(19, 'masin', NULL, 'masin@gmail.com', '$2y$10$sPtBDncZWRu1Bt6FU8uKGuYQzORziWyn3K4MbFMyx6tn/4cIFsRcu', NULL, NULL, NULL, NULL, NULL, 0, 'free', 'student', '2026-02-09 08:30:49', '2026-02-09 08:30:49'),
(20, 'ahmadfarhan', 'Ahmad Farhan Bin Mohamad', 'ahmadfarhan@gmail.com', '$2y$10$YFLwWBOWD4naN9650qtVGuZ5QvaUtExvnPIDZQ2FEdMjAOWjnuv.e', '2002-01-24', 'Malaysia', '0115097180', 'Male', '', 1, 'premium', 'student', '2026-02-09 09:16:10', '2026-02-09 09:31:15');

-- --------------------------------------------------------

--
-- Table structure for table `video_progress`
--

CREATE TABLE `video_progress` (
  `id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `course_id` int(11) NOT NULL,
  `video_id` int(11) NOT NULL,
  `watched` tinyint(1) DEFAULT 0,
  `watched_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `video_progress`
--

INSERT INTO `video_progress` (`id`, `student_id`, `course_id`, `video_id`, `watched`, `watched_at`) VALUES
(13, 4, 1, 1, 1, '2026-01-25 00:17:32'),
(14, 4, 1, 2, 1, '2026-01-25 00:17:36'),
(15, 4, 1, 3, 1, '2026-01-25 00:17:37'),
(16, 4, 1, 4, 1, '2026-01-25 00:17:39'),
(17, 4, 1, 5, 1, '2026-01-25 00:17:42'),
(18, 4, 2, 1, 1, '2026-02-03 09:24:21'),
(19, 4, 2, 2, 1, '2026-02-03 09:39:46'),
(20, 4, 2, 3, 1, '2026-02-03 09:39:49'),
(21, 4, 2, 4, 1, '2026-02-03 09:39:53'),
(22, 4, 2, 5, 1, '2026-02-03 09:39:58'),
(23, 10, 2, 1, 1, '2026-02-03 09:55:01'),
(24, 10, 2, 2, 1, '2026-02-03 09:58:23'),
(25, 10, 2, 3, 1, '2026-02-03 09:58:26'),
(26, 10, 2, 4, 1, '2026-02-03 09:58:29'),
(27, 10, 2, 5, 1, '2026-02-03 09:58:31'),
(28, 10, 1, 1, 1, '2026-02-03 10:59:00'),
(29, 10, 1, 2, 1, '2026-02-03 10:59:01'),
(38, 10, 3, 2, 1, '2026-02-03 21:13:11'),
(39, 10, 3, 3, 1, '2026-02-03 21:13:14'),
(40, 10, 3, 4, 1, '2026-02-03 21:13:16'),
(41, 10, 4, 2, 1, '2026-02-04 14:59:35'),
(42, 10, 4, 3, 1, '2026-02-04 14:59:39'),
(43, 10, 4, 4, 1, '2026-02-04 14:59:42'),
(44, 10, 5, 1, 1, '2026-02-04 19:03:32'),
(45, 10, 5, 2, 1, '2026-02-04 19:03:35'),
(46, 10, 5, 3, 1, '2026-02-04 19:03:37'),
(47, 10, 5, 4, 1, '2026-02-04 19:03:39'),
(48, 4, 5, 1, 1, '2026-02-05 06:54:22'),
(49, 4, 5, 2, 1, '2026-02-05 06:54:24'),
(50, 4, 5, 3, 1, '2026-02-05 06:54:26'),
(51, 4, 5, 4, 1, '2026-02-05 06:54:28'),
(52, 14, 1, 1, 1, '2026-02-05 07:30:06'),
(53, 14, 1, 2, 1, '2026-02-05 07:30:09'),
(54, 14, 1, 3, 1, '2026-02-05 07:30:11'),
(55, 14, 1, 4, 1, '2026-02-05 07:30:14'),
(56, 14, 1, 5, 1, '2026-02-05 07:30:15'),
(57, 14, 5, 1, 1, '2026-02-05 09:32:01'),
(58, 14, 5, 2, 1, '2026-02-05 09:32:05'),
(59, 14, 5, 3, 1, '2026-02-05 09:32:39'),
(60, 14, 5, 4, 1, '2026-02-05 09:32:59'),
(61, 14, 2, 1, 1, '2026-02-05 09:37:58'),
(62, 14, 2, 2, 1, '2026-02-05 09:38:43'),
(63, 14, 2, 3, 1, '2026-02-05 09:39:01'),
(64, 14, 2, 4, 1, '2026-02-05 09:39:21'),
(65, 14, 2, 5, 1, '2026-02-05 09:39:49'),
(66, 14, 4, 2, 1, '2026-02-05 09:40:31'),
(67, 14, 4, 3, 1, '2026-02-05 09:41:12'),
(68, 14, 4, 4, 1, '2026-02-05 09:41:36'),
(69, 10, 1, 3, 1, '2026-02-07 22:09:39'),
(70, 10, 1, 4, 1, '2026-02-07 22:09:41'),
(71, 10, 1, 5, 1, '2026-02-07 22:09:42'),
(72, 16, 1, 1, 1, '2026-02-07 22:11:02'),
(73, 16, 1, 2, 1, '2026-02-07 22:11:05'),
(74, 16, 1, 3, 1, '2026-02-07 22:11:07'),
(75, 16, 1, 4, 1, '2026-02-07 22:11:08'),
(76, 16, 1, 5, 1, '2026-02-07 22:11:10'),
(77, 16, 2, 1, 1, '2026-02-07 22:11:19'),
(78, 16, 2, 2, 1, '2026-02-07 22:11:21'),
(79, 16, 2, 3, 1, '2026-02-07 22:11:23'),
(80, 16, 2, 4, 1, '2026-02-07 22:11:24'),
(81, 16, 2, 5, 1, '2026-02-07 22:11:25'),
(82, 16, 4, 2, 1, '2026-02-07 22:11:40'),
(83, 16, 4, 3, 1, '2026-02-07 22:11:41'),
(84, 16, 4, 4, 1, '2026-02-07 22:11:42'),
(85, 16, 3, 2, 1, '2026-02-07 22:11:47'),
(86, 16, 3, 3, 1, '2026-02-07 22:11:48'),
(87, 16, 3, 4, 1, '2026-02-07 22:11:49'),
(88, 16, 5, 1, 1, '2026-02-07 22:12:09'),
(89, 16, 5, 2, 1, '2026-02-07 22:12:11'),
(90, 16, 5, 3, 1, '2026-02-07 22:12:12'),
(91, 16, 5, 4, 1, '2026-02-07 22:12:14'),
(92, 17, 1, 1, 1, '2026-02-08 20:10:25'),
(93, 17, 1, 2, 1, '2026-02-08 20:10:28'),
(94, 17, 1, 3, 1, '2026-02-08 20:10:31'),
(95, 17, 1, 4, 1, '2026-02-08 20:10:35'),
(96, 17, 1, 5, 1, '2026-02-08 20:10:37'),
(97, 17, 5, 1, 1, '2026-02-08 20:11:18'),
(98, 17, 5, 2, 1, '2026-02-08 20:11:21'),
(99, 17, 5, 3, 1, '2026-02-08 20:11:23'),
(100, 17, 5, 4, 1, '2026-02-08 20:11:24'),
(105, 18, 5, 1, 1, '2026-02-08 20:27:09'),
(106, 18, 5, 2, 1, '2026-02-08 20:27:18'),
(107, 18, 5, 3, 1, '2026-02-08 20:27:20'),
(108, 18, 5, 4, 1, '2026-02-08 20:27:22'),
(109, 18, 3, 2, 1, '2026-02-08 20:27:51'),
(110, 18, 3, 3, 1, '2026-02-08 20:27:54'),
(111, 18, 3, 4, 1, '2026-02-08 20:27:56'),
(112, 18, 4, 2, 1, '2026-02-08 20:28:03'),
(113, 18, 4, 3, 1, '2026-02-08 20:28:06'),
(114, 18, 4, 4, 1, '2026-02-08 20:28:07'),
(115, 18, 1, 1, 1, '2026-02-08 20:28:18'),
(116, 18, 1, 2, 1, '2026-02-08 20:28:20'),
(117, 18, 1, 3, 1, '2026-02-08 20:28:21'),
(130, 18, 1, 4, 1, '2026-02-09 08:32:59'),
(131, 18, 1, 5, 1, '2026-02-09 08:33:06'),
(132, 18, 2, 1, 1, '2026-02-09 08:33:56'),
(133, 20, 1, 1, 1, '2026-02-09 09:21:13'),
(134, 20, 1, 2, 1, '2026-02-09 09:21:36'),
(135, 20, 1, 3, 1, '2026-02-09 09:21:58'),
(136, 20, 1, 4, 1, '2026-02-09 09:22:08'),
(137, 20, 1, 5, 1, '2026-02-09 09:22:17'),
(138, 20, 4, 2, 1, '2026-02-09 09:24:27'),
(139, 20, 4, 3, 1, '2026-02-09 09:24:37'),
(140, 20, 4, 4, 1, '2026-02-09 09:24:48');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `courses`
--
ALTER TABLE `courses`
  ADD PRIMARY KEY (`course_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `exercises`
--
ALTER TABLE `exercises`
  ADD PRIMARY KEY (`exercise_id`),
  ADD KEY `fk_exercises_lesson` (`lesson_id`);

--
-- Indexes for table `lessons`
--
ALTER TABLE `lessons`
  ADD PRIMARY KEY (`lesson_id`),
  ADD KEY `fk_lessons_course` (`course_id`);

--
-- Indexes for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD PRIMARY KEY (`quiz_id`),
  ADD KEY `fk_quiz_course` (`course_id`),
  ADD KEY `fk_quiz_lesson` (`lesson_id`);

--
-- Indexes for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD PRIMARY KEY (`attempt_id`),
  ADD KEY `fk_attempt_student` (`student_id`),
  ADD KEY `fk_attempt_quiz` (`quiz_id`);

--
-- Indexes for table `submissions`
--
ALTER TABLE `submissions`
  ADD PRIMARY KEY (`submission_id`),
  ADD KEY `fk_submissions_exercise` (`exercise_id`),
  ADD KEY `fk_submissions_student` (`student_id`);

--
-- Indexes for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD PRIMARY KEY (`subscription_id`),
  ADD KEY `fk_subscriptions_student` (`student_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `idx_subscription_type` (`subscription_type`);

--
-- Indexes for table `video_progress`
--
ALTER TABLE `video_progress`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_video_progress` (`student_id`,`course_id`,`video_id`),
  ADD KEY `fk_video_progress_student` (`student_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `courses`
--
ALTER TABLE `courses`
  MODIFY `course_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `exercises`
--
ALTER TABLE `exercises`
  MODIFY `exercise_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lessons`
--
ALTER TABLE `lessons`
  MODIFY `lesson_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=41;

--
-- AUTO_INCREMENT for table `quizzes`
--
ALTER TABLE `quizzes`
  MODIFY `quiz_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=114;

--
-- AUTO_INCREMENT for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  MODIFY `attempt_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=603;

--
-- AUTO_INCREMENT for table `submissions`
--
ALTER TABLE `submissions`
  MODIFY `submission_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `subscriptions`
--
ALTER TABLE `subscriptions`
  MODIFY `subscription_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `video_progress`
--
ALTER TABLE `video_progress`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=141;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `courses`
--
ALTER TABLE `courses`
  ADD CONSTRAINT `fk_courses_user` FOREIGN KEY (`created_by`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `exercises`
--
ALTER TABLE `exercises`
  ADD CONSTRAINT `fk_exercises_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE CASCADE;

--
-- Constraints for table `lessons`
--
ALTER TABLE `lessons`
  ADD CONSTRAINT `fk_lessons_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE;

--
-- Constraints for table `quizzes`
--
ALTER TABLE `quizzes`
  ADD CONSTRAINT `fk_quiz_course` FOREIGN KEY (`course_id`) REFERENCES `courses` (`course_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_quiz_lesson` FOREIGN KEY (`lesson_id`) REFERENCES `lessons` (`lesson_id`) ON DELETE SET NULL;

--
-- Constraints for table `quiz_attempts`
--
ALTER TABLE `quiz_attempts`
  ADD CONSTRAINT `fk_attempt_quiz` FOREIGN KEY (`quiz_id`) REFERENCES `quizzes` (`quiz_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_attempt_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `submissions`
--
ALTER TABLE `submissions`
  ADD CONSTRAINT `fk_submissions_exercise` FOREIGN KEY (`exercise_id`) REFERENCES `exercises` (`exercise_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_submissions_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `subscriptions`
--
ALTER TABLE `subscriptions`
  ADD CONSTRAINT `fk_subscriptions_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `video_progress`
--
ALTER TABLE `video_progress`
  ADD CONSTRAINT `fk_video_progress_student` FOREIGN KEY (`student_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
