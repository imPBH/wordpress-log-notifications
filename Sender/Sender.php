<?php

class Sender
{
    private $discord;
    private $slack;
    private $telegram;

    public function __construct()
    {
        $this->discord = new Discord();
        $this->slack = new Slack();
        $this->telegram = new Telegram();
    }

    public static function Send($data, $url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);
    }

    public function NewComment($comment_id)
    {
        $comment = get_comment($comment_id);
        $postID = $comment->comment_post_ID;
        $postUrl = get_post_permalink($postID);
        $post = get_post($postID);
        $postTitle = $post->post_title;
        $timestamp = date("c", strtotime("now"));
        $timestampSlack = time();
        $author = $comment->comment_author;

        if ($author == "") {
            $author = "Anonymous user";
        }

        $this->discord->NewComment($comment, $postUrl, $postTitle, $author, $timestamp);
        $this->slack->NewComment($comment, $postUrl, $postTitle, $author, $timestampSlack);
        $this->telegram->NewComment($comment, $postUrl, $postTitle, $author, $timestamp);
    }

    public function CommentStatusUpdate($new_status, $old_status, $comment)
    {
        $postID = $comment->comment_post_ID;
        $postUrl = get_post_permalink($postID);
        $post = get_post($postID);
        $postTitle = $post->post_title;
        $timestamp = date("c", strtotime("now"));
        $timestampSlack = time();
        $author = $comment->comment_author;

        if ($author == "") {
            $author = "Anonymous user";
        }

        $color = hexdec("3366ff");
        $title = ":tools: Update detected on a comment :tools:";
        $titleTelegram = "\xF0\x9F\x94\xA8 Update detected on a comment \xF0\x9F\x94\xA8";

        Sender::Send("$old_status -> $new_status", "https://requestbin.io/zm5v86zm");
        if ($old_status != $new_status) {
            switch ($new_status) {
                case "approved":
                    $color = hexdec("7CFC00");
                    $title = ":white_check_mark: Approved comment :white_check_mark:";
                    $titleTelegram = "\xE2\x9C\x85 Approved comment \xE2\x9C\x85";
                    break;
                case "delete":
                    $color = hexdec("FF0000");
                    $title = ":x::wastebasket: Comment permanently deleted :wastebasket::x:";
                    $titleTelegram = "\xE2\x9D\x8C\xF0\x9F\x97\x91 Comment permanently deleted \xF0\x9F\x97\x91\xE2\x9D\x8C";
                    break;
                case "trash":
                    $color = hexdec("FF0000");
                    $title = ":wastebasket: Comment trashed :wastebasket:";
                    $titleTelegram = "\xF0\x9F\x97\x91 Comment trashed \xF0\x9F\x97\x91";
                    break;
                case "spam":
                    $color = hexdec("FF0000");
                    $title = ":x: Comment put in the spam section :x:";
                    $titleTelegram = "\xE2\x9D\x8C Comment put in the spam section \xE2\x9D\x8C";
                    break;
                case "unapproved":
                    $color = hexdec("FF8C00");
                    $title = ":grey_question: Unapproved comment :grey_question:";
                    $titleTelegram = "\xE2\x9D\x94 Unapproved comment \xE2\x9D\x94";
                    break;
            }
        }

        $this->discord->CommentStatusUpdate($title, $color, $comment, $postUrl, $postTitle, $author, $old_status, $new_status, $timestamp);
        $this->slack->CommentStatusUpdate($title, $color, $comment, $postUrl, $postTitle, $author, $old_status, $new_status, $timestampSlack);
        $this->telegram->CommentStatusUpdate($titleTelegram, $color, $comment, $postUrl, $postTitle, $author, $old_status, $new_status, $timestamp);
    }

    public function PostUpdate($newStatus, $oldStatus, $post)
    {
        $postID = $post->ID;
        $postUrl = get_post_permalink($postID);
        $postTitle = $post->post_title;
        $postAuthorID = $post->post_author;
        $postAuthor = get_user_by("id", $postAuthorID);
        $postAuthorName = $postAuthor->display_name;
        $timestamp = date("c", strtotime("now"));
        $timestampSlack = time();

        $title = "";
        $titleTelegram = "";
        $color = "";
        switch ($newStatus) {
            case "publish":
                switch ($oldStatus) {
                    case "auto-draft":
                    case "draft":
                        $title = ":rotating_light: New post detected ! :rotating_light:";
                        $titleTelegram = "\xF0\x9F\x9A\xA8 New post detected ! \xF0\x9F\x9A\xA8";
                        $color = hexdec("7CFC00");
                        break;
                    case "publish":
                        $title = ":pencil: Update detected on a post ! :pencil:";
                        $titleTelegram = "\xF0\x9F\x93\x9D Update detected on a post ! \xF0\x9F\x93\x9D";
                        $color = hexdec("FF8C00");
                        break;
                }
                break;
            case "trash":
                $title = ":wastebasket: Post trashed ! :wastebasket:";
                $titleTelegram = "\xF0\x9F\x97\x91 Post trashed ! \xF0\x9F\x97\x91";
                $color = hexdec("FF0000");
                break;
            case "draft":
                $title = ":grey_question: New post in draft ! :grey_question:";
                $titleTelegram = "\xE2\x9D\x94 New post in draft ! \xE2\x9D\x94";
                $color = hexdec("FF8C00");
                break;
        }
        if ($title != "" && $titleTelegram != "") {
            $this->discord->PostUpdate($title, $color, $post, $postID, $postUrl, $postTitle, $postAuthorName, $timestamp);
            $this->slack->PostUpdate($title, $color, $post, $postID, $postUrl, $postTitle, $postAuthorName, $timestampSlack);
            $this->telegram->PostUpdate($titleTelegram, $color, $post, $postID, $postUrl, $postTitle, $postAuthorName, $timestamp);
        }
    }
}