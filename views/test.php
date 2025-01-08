<!DOCTYPE html>
<html>
<head>
    <title>PHP Script Runner</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
        }
        #editor, #output {
            width: 50%;
            height: 100%;
            padding: 20px;
            box-sizing: border-box;
        }
        #editor {
            background: #f5f5f5;
        }
        #output {
            background: white;
            border-left: 1px solid #ccc;
        }
        textarea {
            width: 100%;
            height: 80%;
            resize: none;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div id="editor">
        <h3>PHP Script Editor</h3>
        <form method="POST" action="">
            <textarea name="code" style="height: 80vh; width: 100%;"><?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : '// Write your PHP code here\n'; ?></textarea>
            <br><br>
            <input type="submit" value="Run Code">
        </form>
    </div>
    <div id="output">
        <h3>Output</h3>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['code'])) {
            echo '<pre>';
            ob_start();
            try {
                eval($_POST['code']);
            } catch (Exception $e) {
                echo 'Error: ' . $e->getMessage();
            }
            $output = ob_get_clean();
            echo htmlspecialchars($output);
            echo '</pre>';
        }
        ?>
    </div>
</body>
</html>