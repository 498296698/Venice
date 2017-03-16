<?php
// 文章地址：http://www.cnblogs.com/dee0912/p/5444780.html

$seconds = 7 * 86400; // 一周的秒数
define('ONE_WEEK_IN_SECONDS', $seconds);

define('VOTE_SCORE', 1); // 每投一票文章加的分值
define('ARTICLES_PER_PAGE', 5); // 每页5条数据

// 投票系统
class Vote
{
	private static $redis = null;
    private static $redisConf = ['host'=>'localhost', 'port'=>6379];
    private static $redisSelect = 0;

	private static function getRedis()
    {
        if (!self::$redis) {
            $redis = new Redis();
            if (!$redis->connect(self::$redisConf['host'], self::$redisConf['port'])) {
                throw new Exception("Error Redis Connect", 100);
            }
            $redis->select(self::$redisSelect);
            self::$redis = $redis;
        }
		// $redis->flushDB();exit; // 清空当前数据库

        return self::$redis;
    }

	/**
	 * 发布文章
	 * @param  int $user_id 作者id
	 * @param  string $title 文章标题
	 * @param  string $link 文章链接
	 * @return int 文章id
	 */
	public static function postArticle($user_id, $title, $link)
	{
		$redis = self::getRedis();

		$article_id = $redis->incr('article:'); // 文章数量加1,并生成新的文章id
	    $voted_key = 'voted:'.$article_id;

	    $redis->sadd($voted_key, $user_id); // 将发布文章的用户添加到文章已投票的用户名单中
	    $redis->expire($voted_key, ONE_WEEK_IN_SECONDS); // 将投票名单的过期时间设置为一周

	    // 将文章的信息存储到一个散列里
	    $now = time();
	    $article_key = 'article:'.$article_id;
	    $redis->hmset($article_key, [
	        'title' => $title,
	        'link' => $link,
	        'user_id' => $user_id,
	        'time' => $now,
	        'votes'=> 1
	    ]); 

	    $redis->zadd('score:', 1, $article_key); // 把文章添加到根据评分排序的有序集合中

	    $redis->zadd('time:', $now, $article_key); // 把文章添加到根据发布时间排序的有序集合中

	    return $article_id;
	}

	/**
	 * 给文章投票
	 * @param  int $user_id 用户id
	 * @param  int $article_id 文章id
	 * @return bool
	 */
	public static function articleVote($user_id, $article_id) 
	{
		$redis = self::getRedis();

	    $cutoff = time() - ONE_WEEK_IN_SECONDS;
	    if(intval($redis->zscore('time:', 'article:'.$article_id)) < $cutoff) {
	        return false; // 投票时间已过期
	    }

	    if ($redis->sadd('voted:'.$article_id, $user_id)) { // 将用户添加已投票的用户名单中
	        // 为有序集 score 的成员增加分数
	        $score_new = $redis->zincrby('score:', VOTE_SCORE, 'article:'.$article_id);
	        echo $score_new;

	    } else {
	        return false;
	    }   

	    return true;
	}

	/**
	 * 获取文章列表
	 * @param  int $page 页码
	 * @param  string $order 根据$order来排序
	 * @return array 文章列表
	 */
	public static function getArticles($page, $order = 'score:') 
	{
		$redis = self::getRedis();

	    $start = ($page - 1) * ARTICLES_PER_PAGE;
	    $end = $start + ARTICLES_PER_PAGE - 1;

	    $ids = $redis->zrevrange($order, $start, $end); // 获取多个文件的序号 按 score 值递减(从大到小)来排列。

	    $articles = [];
	    foreach ($ids as $id) {
	        $article_data = $redis->hgetall($id);
	        $article_data['id'] = $id;
	        $articles[] = $article_data;
	    }

	    return $articles;
	}
}

if ($_GET['t'] == '01') {
	// 添加文章
	$user_id = empty($_GET['user_id']) ? 1 : (int)$_GET['user_id'];
	$mtid = mt_rand(0,999);
	$title = '文章标题'.$mtid;
	$link = 'http://www.youdomain.com/article/'.$mtid;

	if (Vote::postArticle($user_id, $title, $link)) {
	    echo 'success';
	} else {
	    echo 'error';
	}
} elseif ($_GET['t'] == '02') {
	// 投票
	$user_id = empty($_GET['user_id']) ? 0 : (int)$_GET['user_id'];
	$article_id = empty($_GET['article_id']) ? 0 : (int)$_GET['article_id'];
	if(!Vote::articleVote($user_id, $article_id)) {
	    echo '投票失败';
	} else {
	    echo '投票成功';
	}
} elseif ($_GET['t'] == '03') {
	// 获取文章列表
	$page = 1;
	$articles = Vote::getArticles($page);
	echo '<pre>';
	print_r($articles);
}


