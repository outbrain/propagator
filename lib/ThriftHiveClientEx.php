<?php

/**
 * ThriftHiveClientEx
 *
 * デフォルトのThriftHiveClientの場合、
 * socketのopen & close がTSocketクラスでしか行えない。
 *
 * TSocketインスタンスは、ThriftHiveClientインスタンスが
 * 参照しているがopenするメソッドがない為、ThriftHiveClientを
 * 継承してopen & close 用のメソッド作成した。
 *
 * @uses ThriftHiveClient
 * @author noto
 */
class ThriftHiveClientEx extends ThriftHiveClient {


    /**
     * __construct
     * @param      $input
     * @param null $output
     */
    public function __construct($input, $output = null) {
        parent::__construct($input, $output);
    }


    /**
     * open
     * @return void
     */
    public function open() {
        if (!$this->input_->getTransport()->isOpen()) {
            $this->input_->getTransport()->open();
        }
    }


    /**
     * close
     * @return void
     */
    public function close() {
        if ($this->input_->getTransport()->isOpen()) {
            $this->input_->getTransport()->close();
        }
    }


    /**
     * execute
     * @param $str
     * @return bool
     * @throws HiveExecuteException
     */
    public function execute($str) {

        // あるクエリーの前に実行したいHQLが存在する場合
        // 「;」で区切って連続実行させる
        // ex) use my_db; select * from my_db
        $queries = preg_split('/;/', $str);

        foreach ($queries as $query) {
            $query = str_replace(array('\r\n', '\n', '\r'),  ' ', $query);
            $query = ltrim($query);
            if ($query == '') return false;

            try {
                parent::execute($query);
            } catch (Exception $e) {
                $msg = $e->getMessage();
                $msg = "HiveExecuteException: Execute Error:: $msg  query:: $query ";
                throw new HiveExecuteException($msg);
            }
        }
    }
}

class HiveExecuteException extends Exception {
}
