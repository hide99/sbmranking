<?=$javascript->link('/sbmranking/js/jquery.timers.js',false);?>

<script type="text/javascript">
//設定すべきもの
var baseurl = "<?=$sbmsettings['baseurl'];?>";
var foreign_key = "<?=$sbmsettings['foreign_key'];?>";//ユーザーIDや、ページIDなど
var addsbmurl = "<?=DS.$this->params['controller'].DS.'addsbm'.DS;?>";

//取得したいURL
var endcount = 0;//固定
var endbookmark = 3;//この回数分、ブックマークを取得したらDBに突っ込む!

//ブックマーク数の初期値

var hatena = 0;
var facebook_like = 0;
var facebook_share = 0;
var facebook_comment = 0;
var facebook_total = 0;
var twitter = 0;

$(function(){
	
	//はてなブックマーク数取得
    function hatenaCount(data) {
        if (data > 0) {
			hatena = data;
            var link = '<a href="http://b.hatena.ne.jp/entry/' + baseurl + '">' + data + '</a>';
        }
		
		endcount++;
		addRanking();
    }
	
    $.getJSON('http://api.b.st-hatena.com/entry.count?url=' + encodeURIComponent(baseurl) + '&callback=?', hatenaCount);
	
	//FaceBookブックマーク数を取得
	function facebookCount(json) {
		
		facebook_like = json[0]['like_count'];
		facebook_share = json[0]['share_count'];
		facebook_comment = json[0]['comment_count'];
		facebook_total = json[0]['total_count'];
		
		endcount++;
		addRanking();
	}
	
	$.ajax({
        type: 'GET',
        dataType: 'jsonp',
        url: 'https://api.facebook.com/method/links.getStats?format=json&callback=facebookCount&urls=' + baseurl,
        success: facebookCount
    });
	
	//ツイッター言及数
    function topsyCount(data) {
        var count = data.response.all;
        if (count > 0) {
			twitter = count;
            var link = '<a href="' + data.response.topsy_trackback_url + '">' + count + '</a>';
        }
		
		endcount++;
		addRanking();
    }

    $.getJSON('http://otter.topsy.com/stats.js?url=' + encodeURIComponent(baseurl) + '&callback=?', topsyCount);
});


function addRanking()
{
	if(endcount == endbookmark){
		//タイマースタート
		getRanking();
	}
}

//タイマーでAJAX
<? if(Configure::read('sbmranking.ajax')):?>
$(document).everyTime(10000,function(){
	getRanking();
});
<? endif;?>



//DBに保存する
function getRanking()
{
	$.ajax({
		type: "POST",
		url: addsbmurl,
		data: "hatena=" + hatena
			  + "&foreign_key=" + foreign_key
			  + "&facebook_like=" + facebook_like
			  + "&facebook_share=" + facebook_share
			  + "&facebook_comment=" + facebook_comment
			  + "&facebook_total=" + facebook_total
			  + "&twitter=" + twitter,
		
		beforeSend: function(){
			$('#sbmpoint').html('更新中...');
		},
		
		success: function(msg){
			$('#sbmpoint').text(msg);
		}
	});	
}




</script>
