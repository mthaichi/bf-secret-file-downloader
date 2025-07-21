import React from 'react';
import {
  Box,
  Typography,
  Paper,
  TextField,
  Switch,
  FormControlLabel,
  Button,
  Divider,
} from '@mui/material';

const Settings: React.FC = () => {
  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        設定
      </Typography>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          基本設定
        </Typography>
        <Divider sx={{ mb: 2 }} />

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 3 }}>
          <TextField
            fullWidth
            label="プラグイン名"
            defaultValue="BF Secret File Downloader"
            margin="normal"
          />
          <TextField
            fullWidth
            label="バージョン"
            defaultValue="1.0.0"
            margin="normal"
            disabled
          />
          <TextField
            fullWidth
            label="ファイル保存ディレクトリ"
            defaultValue="/wp-content/uploads/secret-files/"
            margin="normal"
          />
          <TextField
            fullWidth
            label="最大ファイルサイズ (MB)"
            defaultValue="10"
            margin="normal"
            type="number"
          />
        </Box>
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          セキュリティ設定
        </Typography>
        <Divider sx={{ mb: 2 }} />

        <FormControlLabel
          control={<Switch defaultChecked />}
          label="BASIC認証を有効にする"
        />
        <br />
        <FormControlLabel
          control={<Switch defaultChecked />}
          label="ダウンロード履歴を記録する"
        />
        <br />
        <FormControlLabel
          control={<Switch />}
          label="IPアドレス制限を有効にする"
        />
        <br />
        <FormControlLabel
          control={<Switch defaultChecked />}
          label="ファイルタイプ制限を有効にする"
        />
      </Paper>

      <Paper sx={{ p: 3, mb: 3 }}>
        <Typography variant="h6" gutterBottom>
          通知設定
        </Typography>
        <Divider sx={{ mb: 2 }} />

        <FormControlLabel
          control={<Switch defaultChecked />}
          label="新しいファイル追加時にメール通知"
        />
        <br />
        <FormControlLabel
          control={<Switch />}
          label="ダウンロード時にメール通知"
        />
        <br />
        <FormControlLabel
          control={<Switch defaultChecked />}
          label="エラー発生時にメール通知"
        />
      </Paper>

      <Box display="flex" gap={2}>
        <Button variant="contained" color="primary">
          設定を保存
        </Button>
        <Button variant="outlined">
          デフォルトに戻す
        </Button>
      </Box>
    </Box>
  );
};

export default Settings;