<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>掲示板</title>
</head>
<body>



    <?php
	//DB接続設定
	$dsn = 'データベース名';
	$user = 'ユーザー名';
	$password = 'パスワード' ;
	$pdo = new PDO($dsn, $user, $password, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));
//    $pdo = new PDO($dsn, $user, $password);

    //data base内にtableを作成
    $sql = "CREATE TABLE IF NOT EXISTS BulletinBoard"	//SQL文中の「 IF NOT EXISTS 」は「もしまだこのテーブルが存在しないなら」という意味
	." ("
	. "id INT AUTO_INCREMENT PRIMARY KEY,"
	. "name char(32),"
    . "comment TEXT,"
    . "date TEXT,"
    . "password char(32)"
	.");";
	$stmt = $pdo->query($sql);




    $id = ":id";
	$name = $_POST["name"];          // 変数にpostされた文字列(名前)を代入
    $comment = $_POST["comment"] ;        // 変数にpostされた文字列(コメント)を代入
    $date = date("Y年m月d日 H時i分s秒") ;     // 変数にpostされた文字列を代入&改行
    $delete = $_POST["delete"];        // 変数にpostされた「削除する番号」を代入
    $redact = $_POST["redact"];        // 変数にpostされた「編集する番号」を代入
    $redact_num = $_POST["redact_num"]; 
    $password = $_POST["password"];
    $delete_password = $_POST["delete_password"];
    $redact_password = $_POST["redact_password"];


	//data baseへdataを入力
	if((isset($name) && isset($comment)) && empty($delete) && empty($redact)){
        if(!empty($redact_num)){ //data base上にあるdata recordを編集（編集対象番号の行だけ更新する）
	        $sql = $pdo->prepare('UPDATE BulletinBoard SET name=:name,comment=:comment,date=:date, password=:password WHERE id=:redact_num');
	        $sql->bindParam(':name', $name, PDO::PARAM_STR);
	        $sql->bindParam(':comment', $comment, PDO::PARAM_STR);
			$sql->bindParam(':date', $date, PDO::PARAM_STR);
            $sql->bindParam(':password', $password, PDO::PARAM_STR);
            $sql->bindParam(':redact_num', $redact_num, PDO::PARAM_INT);
            $sql->execute();
		}else{  //data baseへ新規dataを入力（編集番号:hiddenのやつが空）
            $sql = $pdo -> prepare("INSERT INTO BulletinBoard (name, comment, date, password) VALUES (:name, :comment, :date, :password)");
			$sql -> bindParam(':name', $name, PDO::PARAM_STR);
			$sql -> bindParam(':comment', $comment, PDO::PARAM_STR);
			$sql -> bindParam(':date', $date, PDO::PARAM_STR);
			$sql -> bindParam(':password', $password, PDO::PARAM_STR);
            $sql -> execute();
        }
    }


    //data base上にあるdata recordを削除
    if(!empty($delete) && !empty($delete_password)){            //「削除番号」あり
        $sql = $pdo->prepare('SELECT * FROM BulletinBoard WHERE id=:delete');   //データベースから削除対象番号のデータを取ってくる
        $sql->bindParam(':delete', $delete, PDO::PARAM_INT); // ←その差し替えるパラメータの値を指定してから、
        $sql->execute();                             // ←SQLを実行する。
	    $edit = $sql->fetch(); 
            if($edit["password"]==$delete_password){    //送信したパスワードとデータベースから取ってきたパスワードが一致する
                $stmt = $pdo->prepare('DELETE FROM BulletinBoard WHERE id=:delete ');
                $stmt->bindParam(':delete', $delete, PDO::PARAM_INT);
                $stmt->execute();
            }
    }



    //data base上にあるdata recordの番号と編集番号一致確認
    if(!empty($redact) && !empty($redact_password)){            //「編集番号」あり
	    $sql = $pdo->prepare('SELECT * FROM BulletinBoard WHERE id=:redact');   // データベースから編集対象番号のデータを取ってくる
	    $sql->bindParam(':redact', $redact, PDO::PARAM_INT); // ←その差し替えるパラメータの値を指定してから、
        $sql->execute();                             // ←SQLを実行する。
	    $edit = $sql->fetch(); 
            if($edit["id"]==$redact){   //送信したパスワードとデータベースから取ってきたパスワードが一致する
                //投稿フォームに表示するために、番号・名前・コメント・パスワードを適当な変数に入れる
                $newnum = $edit['id'];
	            $newname = $edit['name'];
                $newcomment = $edit['comment'];
            }
	}


    ?>



    <form action="" method="post">
        <input type="text" name="name" placeholder="Name" value="<?php
                if(!empty($newname)){
                    echo $newname;
                }
            ?>"> <br>     <!-- 名前記入欄 -->
        <input type="text" name="comment" placeholder="Comment" value="<?php 
                if(!empty($newcomment)){
                    echo $newcomment;
                }
            ?>"> <br>     <!-- コメント記入欄 -->
        <input type="password" name="password" placeholder="Password">        <!-- パスワード記入欄 -->
        <input type="hidden" name="redact_num" placeholder=""value="<?php
                if(!empty($newnum)){
                    echo $newnum;
                }
            ?>">    <!-- 「編集番号」転記欄（ページ上では見えなくする） -->
        <input type="submit" name="submit"> <br>                      <!-- 送信ボタン -->
        <br>
        <input type="number" name="delete" placeholder="Delete(Number)"> <br>      <!-- 削除する番号記入欄 -->
        <input type="password" name="delete_password" placeholder="Password">        <!-- パスワード記入欄 -->
        <input type="submit" name="submit" value="削除"> <br>                    <!-- 「削除」送信ボタン -->
        <br>
        <input type="number" name="redact" placeholder="Redact(Number)"> <br>      <!-- 編集する番号記入欄 -->
        <input type="password" name="redact_password" placeholder="Password">        <!-- パスワード記入欄 -->
        <input type="submit" name="submit" value="編集">                    <!-- 「編集」送信ボタン -->
    </form>






    <?php

	$sql = 'SELECT * FROM BulletinBoard';   //データベースからすべてのデータを取ってくる
	$stmt = $pdo->query($sql);
	$results = $stmt->fetchAll();
	foreach ($results as $row){ //1投稿ずつ
    //$rowの中にはテーブルのカラム名が入る
    //番号・名前・コメント・日時を表示する
		echo $row['id'].',';
		echo $row['name'].',';
		echo $row['comment'].'.';
		echo $row['date'].'<br>';
		echo "<hr>";
	}

    ?>



</body>
</html>