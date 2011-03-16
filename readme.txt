***************************************************************************
	ソーシャルブックマークランキング プラグイン
	Copyright webservice,inc ( http://www.okws.jp/ )
	
	v 0.1
	とりあえず完成
	
	v 0.2
	デバッグ
	最新のポイントをAJAXで返す
	AJAXタイマーでポイントを読み出す。
***************************************************************************

--------------------------------------------------
■特徴
--------------------------------------------------

・はてな
（ブックマーク数）

・ツイッター
（つぶやき数）

・Facebook
（イイネ数）
（シェア数）
（シェアへのコメント）

をそれぞれ取得できる。

また、 rate としてオリジナルの要素を加味して全ての評価を出せる

//ランキングプラグイン
Configure::write('sbmranking',array(
		//total_point を出す計算式
		'fields' => '(hatena*3 + facebook_like*2 + facebook_share*2 + facebook_comment*2 + facebook_total*2 + twitter*2 + rate*2) as total_point',
		//過去のランキングを作るか？ false にすると 過去のランキングは生成しない。
		'past' => true
	)
);

--------------------------------------------------
■初期設定
--------------------------------------------------

DB作成
/*
    CREATE TABLE `yourprefix_sbm_rankings` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
    `foreign_key` INT NOT NULL ,
    `hatena` INT NOT NULL ,
    `facebook_like` INT NOT NULL ,
    `facebook_share` INT NOT NULL ,
    `facebook_comment` INT NOT NULL ,
    `facebook_total` INT NOT NULL ,
    `twitter` INT NOT NULL ,
	`rate` INT NOT NULL ,
    `modified` DATETIME NOT NULL );
*/

過去のランキング
/*
	CREATE TABLE `yourprefix_sbm_past_rankings` (
	`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
	`foreign_key` INT NOT NULL ,
	`hatena` INT NOT NULL ,
	`facebook_like` INT NOT NULL ,
	`facebook_share` INT NOT NULL ,
	`facebook_comment` INT NOT NULL ,
	`facebook_total` INT NOT NULL ,
	`twitter` INT NOT NULL ,
	`rate` INT NOT NULL ,
	`created` DATE NOT NULL );
*/

bootstrap.php
//ランキングプラグイン
Configure::write('sbmranking',array(
		//total_point を出す計算式
		'fields' => '(hatena*3000 + facebook_like*2000 + facebook_share*1800 + facebook_comment*750 + facebook_total*980 + twitter*19800 + rate*1230 + 50000) as total_point',
		//初期値 0 だった場合のポイント
		'empty' => 50000,
		//AJAXで最新ポイントを更新するか
		'ajax' => true,
		//過去のランキングを作るか？
		'past' => true
	)
);

--------------------------------------------------
■使い方
--------------------------------------------------

モデル
var $actsAs = array('Sbmranking.sbmranking');

コントローラー

//必ず point 変数にしてビューに送る
function index()
{
	$sbmsettings = array(
		'baseurl' => 'http://www.yahoo.co.jp',//解析したいURL 自分自身のURL FULL_BASE_URL.$_SERVER['REQUEST_URI']
		'foreign_key' => 12,//キーにしたいID。記事のIDなど。
	);
	
	$point = $this->User->getPoint($sbmsettings['foreign_key']);
	
	$this->set(
		compact(
			'sbmsettings',
			'point'
		)
	);
}

function top()
{
	//トップ10位を取得
	$point = $this->User->getTops();
	
	//指定した foreign_key 777 に rate 300ポイント追加
	$this->User->addRate(777,300);
	
}

//AJAXでブックマークを登録する
//コンフィグで過去のランキングを生成する場合は過去のランキングを自動生成
function addSbm()
{
	$this->User->addSbm();
	$this->autoRender = false;	
}

ビューを読み出すことで、JSにてポイントを計算する
<?=$this->element('sbmranking', array('plugin' => 'Sbmranking'));?>

最新のポイントを #sbmpoint に返すので、

<div id="sbmpoint">
ここに結果
</div>

をビューに用意。