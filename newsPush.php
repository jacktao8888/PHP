<?php
/**
 * Created by PhpStorm.
 * User: fantang
 * Date: 2017/9/2
 * Time: 下午1:12
 */

class News extends BaseCtrl {
    public function __construct(){
        parent::__construct('News');
    }

    public function index() {
        $position = intval($_REQUEST['type']);
        $deleted = intval($_REQUEST['deleted']);//0：只显示未删除的新闻；1：也显示已删除的新闻
        $from = $_REQUEST['startTime'];
        $to = $_REQUEST['endTime'];

        $condition = 'createTime < ?';
        $params = [];

        if (!empty($to)) {
            $params[] = $to;
        } else {
            $params[] = date(TIME_FORMAT);
        }

        if (!empty($from)) {
            $condition .= ' and createTime > ?';
            $params[] = $from;
        }

        if (!empty($position)) {
            $condition .= ' and type = ?';
            $params[] = $position;
        }

        $condition .= ' and deleted in (0, ?)';
        $params[] = $deleted;

        $dao = new NewsDao();
        $pager = pager(array(
            'base' => 'news/index'.$this->resetGet(),
            'cnt' => $dao->count($condition, $params),
            'cur' => isset($_GET['pager']) ? intval($_GET['pager']) : 1,
        ));

        $news = $dao->findAll(array($condition, $params), $pager['rows'], $pager['start'], 'deleted asc');
        $this->tpl->setFile('manage/news/index')
            ->assign('news', $news)
            ->assign('pager', $pager['html'])
            ->display();
    }

    public function add() {
        if (empty($_POST)) {
            $this->tpl->setFile('manage/news/change')
                ->display();
        } else {
            $title = $_POST['title'];
            $content = $_POST['content'];
            $type = $_POST['type'];

//            debug($content);
//            return false;

            //覆盖原轮播位置的新闻，即删除原有位置的新闻
            $dao = new NewsDao();
//            $preNews = $dao->findAll(array('createTime < ? and position = ? and deleted = 0', array(date(TIME_FORMAT), $type)), 0, 0, '', 'id');

//            debug($content);
//            echo "\n";
//            debug($_FILES);
//            return false;

            //过滤上传的非图片文件
            foreach ($_FILES['uploadedFiles']['name'] as $file) {
                if (!preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|PNG)$/', $file))
                    return $this->alert2go('上传的文件格式不对', url($_SERVER['HTTP_REFERER']));//浏览器回退，会保存之前的编辑
            }

            foreach ($_FILES['uploadedFiles']['error'] as $error ) {
                switch ($error) {
                    case 0:
                        break;
                    case 1:
                    case 2:
                        return $this->alert2go('上传的文件大小超出限制', url('News/add'));
                    case 3:
                        return $this->alert2go('文件只有部分被上传', url('News/add'));
                    case 4:
                        if (preg_match('/data:image\//', $content)) {
                            return $this->alert2go('必须上传内容图片文件', url('News/add'));
                        }
                        break;
                    case 6:
                        return $this->alert2go('找不到临时文件夹', url('News/add'));
                    case 7:
                        return $this->alert2go('文件写入失败', url('News/add'));
                    default:
                        return $this->alert2go('未知上传错误', url('News/add'));
                }
            }

            try{
                $dao->beginTransaction();
                //debug($preNews);
//                if (!empty($preNews)) {
//                    foreach ($preNews as $value) {
//                        $dao->update($value['id'], array('deleted' => 1));
//                    }
//                }

                //debug($_POST);
    //            debug($content);
    //            echo "\n";
    //            debug($_FILES);
    //            return;

                //插入新的新闻
                $newsId = $dao->insert(array('title' => $title, 'createTime' => date(TIME_FORMAT)));

                $update['content'] = $content;

                //图片保存替换content
                if (!empty($_FILES['uploadedFiles']['tmp_name'])) {
                    $convertedContent = self::imgSrcConvert($newsId, $content, $type);
                    if (empty($convertedContent)) return alert2go('上传错误', url('News/index'));
                    $update['content'] = $convertedContent;
                }

                //保存新闻内容
                $update['type'] = $type;

                $dao->update($newsId, $update);

                //debug($content);
    //            return alert2go('添加成功', url('news/index'));
                $dao->commit();
                return alert2go('添加成功', url('news/index'));
            } catch (SQLiteException $e) {
                $dao->rollback();
                return alert2go('添加失败', url($_SERVER['HTTP_REFERER']));
            }
        }
    }

    public function change() {
        if (empty($_POST)) {
            $newsId = intval($_GET['newsId']);
            if (empty($newsId)) return alert2go('新闻ID不能为空', url('news/index'));
            $news = (new NewsDao())->fetch($newsId);

            $this->tpl->setFile('manage/news/change')
                ->assign('news', $news)
                ->display();
        } else {
            $newsId = intval($_REQUEST['newsId']);
            $title = $_POST['title'];
            $newsContent = $_POST['content'];
            $type = $_POST['type'];

            if (empty($newsId)) return json_encode(array(STATUS => 1, MSG => '新闻ID不能为空'));

            //过滤上传的非图片文件
            foreach ($_FILES['uploadedFiles']['name'] as $file) {
                if (!preg_match('/\.(gif|jpg|jpeg|png|GIF|JPG|PNG)$/', $file))
                    return $this->alert2go('上传的文件格式不对', url($_SERVER['HTTP_REFERER']));//浏览器回退，会保存之前的编辑
            }

            foreach ($_FILES['uploadedFiles']['error'] as $error ) {
                switch ($error) {
                    case 0:
                        break;
                    case 1:
                    case 2:
                        return $this->alert2go('上传的文件大小超出限制', url('News/add'));
                    case 3:
                        return $this->alert2go('文件只有部分被上传', url('News/add'));
                    case 4:
                        if (preg_match('/data:image\//', $newsContent)) {
                            return $this->alert2go('必须上传内容图片文件', url('News/add'));
                        }
                        break;
                    case 6:
                        return $this->alert2go('找不到临时文件夹', url('News/add'));
                    case 7:
                        return $this->alert2go('文件写入失败', url('News/add'));
                    default:
                        return $this->alert2go('未知上传错误', url('News/add'));
                }
            }

            //覆盖原轮播位置的新闻，即删除原有位置的新闻
            $dao = new NewsDao();
//            $preNews = $dao->findAll(array('createTime < ? and position = ? and deleted = 0', array(date(TIME_FORMAT), $type)), 0, 0, '', 'id');

            $convertedContent = self::imgSrcConvert($newsId, $newsContent, $type);

            if (empty($convertedContent)) return $this->alert2go('上传错误', url($_SERVER['HTTP_REFERER']));

            try {
                $dao->beginTransaction();
                //debug($preNews);
//                if (!empty($preNews)) {
//                    foreach ($preNews as $value) {
//                        $dao->update($value['id'], array('deleted' => 1));
//                    }
//                }

                $update = array(
                    'title' => $title,
                    'content' => $convertedContent,
                    'editTime' => date(TIME_FORMAT),
                    'type' => $type,
                );
                $dao->update($newsId, $update);

                //debug($title);
                $dao->commit();
                return alert2go('修改成功', url('news/index'));
            } catch (SQLiteException $e) {
                $dao->rollback();
                return alert2go('修改失败', url('news/index'));
            }
        }
    }

    public function delete() {
        $newsId = intval($_REQUEST['newsId']);

        $dao = new NewsDao();
        $dao->update($newsId, array('deleted' => 1, 'editTime' => date(TIME_FORMAT)));

//        return alert2go('删除成功', url('news/index'));
        return array(STATUS => SUCCESS, URL => $_SERVER['HTTP_REFERER']);
    }

    public function reset () {
        $newsId = intval($_REQUEST['newsId']);
        $dao = new NewsDao();
        $dao->update($newsId, array('deleted' => 0, 'editTime' => date(TIME_FORMAT)));
        return array(STATUS => SUCCESS, URL => $_SERVER['HTTP_REFERER']);
    }

    //img src路径替换，保存上传图片至服务器
    static function imgSrcConvert($newsId, $content, $type) {
        switch ($type) {
            case 1:
                $destDir = SITE_ROOT . '/public/img/pc/img/notice/'.$newsId;
                break;
            case 2:
                $destDir = SITE_ROOT . '/public/img/pc/img/news/'.$newsId;
                break;
            case 3:
                $destDir = SITE_ROOT . '/public/img/pc/img/strategy/'.$newsId;
                break;
            case 4:
                $destDir = SITE_ROOT . '/public/img/pc/img/activity/'.$newsId;
                break;
            default:
                return false;
        }

        if (!is_dir($destDir)) {
            mkdir($destDir);
            //debug(111);
        }

        //内容图片文件检测
        $uploadedFiles = $_FILES['uploadedFiles'];

        $imgSrc = [];
        //保存文件到服务器
        foreach ($uploadedFiles['name'] as $key => $file) {
            $tmpFile = $uploadedFiles['tmp_name'][$key];
            $targetFile = $destDir . '/' . $file;

            if (!move_uploaded_file($tmpFile, $targetFile))
                return false;

            $imgSrc[] = $targetFile;
        }

        $imgIndex = 0;
        while ($position1 = strpos($content, 'data:image/')) {
            $position2 = strpos($content, '">', $position1);
            $search = substr($content, $position1, $position2 - $position1);

            //替换img src
            $content = str_replace($search, $imgSrc[$imgIndex], $content);
            $imgIndex++;
            //debug($position2);
        }

        return $content;
    }

    public function toTop() {
        $newsId = $_REQUEST['newsId'];
        $dao = new NewsDao();
        $news = $dao->fetch($newsId);
        if (!empty($news['top'])) return alert2go('已经置顶', url('news/index'));

        if (empty($_POST)) {
            $this->tpl->setFile('manage/news/top')
                ->display();
        } else {

            //生成新闻图片目录
            switch ($news['type']) {
                case 1:
                    $destDir = SITE_ROOT . '/public/img/pc/img/notice/'.$newsId;
                    break;
                case 2:
                    $destDir = SITE_ROOT . '/public/img/pc/img/news/'.$newsId;
                    break;
                case 3:
                    $destDir = SITE_ROOT . '/public/img/pc/img/strategy/'.$newsId;
                    break;
                case 4:
                    $destDir = SITE_ROOT . '/public/img/pc/img/activity/'.$newsId;
                    break;
                default:
                    return false;
            }

            if (!is_dir($destDir)) {
                mkdir($destDir);
                //debug(111);
            }

            //新闻首页图片
            $uploadedIndexImage = $_FILES['indexImage'];
            $error = true;
            $message = '';
            switch ($uploadedIndexImage['error']) {
                case 0:
                    $error = false;
                    break;
                case 1:
                case 2:
                    $message = '上传的文件大小超出限制';
                    break;
                case 3:
                    $message = '文件只有部分被上传';
                    break;
                case 4:
                    $message = '请上传首页图片文件';
                    break;
                case 6:
                    $message = '找不到临时文件夹';
                    break;
                case 7:
                    $message = '文件写入失败';
                    break;
                default:
                    $message = '未知上传错误';
                    break;
            }

            if (!empty($error))
                return alert2go($message, url('News/toTop?newsId='.$newsId));

            //首页图片路径：相对于web_pc.php的文件夹root/app/templates/home，找到root/public/img/img/lunbo/news_[newsId]/[fileName]
            $indexImage = $destDir . '/' . $uploadedIndexImage['name'];
            if (!move_uploaded_file($uploadedIndexImage['tmp_name'], $indexImage))
                return alert2go('上传失败', url('news/toTop?newsId='.$newsId));

            $dao->update($newsId, array('indexImage' => $indexImage, 'top' => 1));
            return alert2go('置顶成功', url('news/index'));
        }
    }

    public function toBottom () {
        $newsId = intval($_GET['newsId']);

        $dao = new NewsDao();

        $news = $dao->fetch($newsId);
        if (empty($news)) return alert2go('ID不能为空', url('news/index'));

        $dao->update($newsId, array('top' => 0));
//        return alert2go('取消置顶成功', url('news/index'));
        return array(STATUS => SUCCESS, URL => $_SERVER['HTTP_REFERER']);
    }
}
