import React from 'react';
import {
  Paper,
  Typography,
  Box,
  Card,
  CardContent,
} from '@mui/material';
import {
  Folder as FolderIcon,
  Download as DownloadIcon,
  Security as SecurityIcon,
  Settings as SettingsIcon,
} from '@mui/icons-material';

const Dashboard: React.FC = () => {
  const stats = [
    {
      title: '管理ファイル数',
      value: '24',
      icon: <FolderIcon sx={{ fontSize: 40, color: 'primary.main' }} />,
    },
    {
      title: 'ダウンロード数',
      value: '1,234',
      icon: <DownloadIcon sx={{ fontSize: 40, color: 'success.main' }} />,
    },
    {
      title: 'セキュリティレベル',
      value: '高',
      icon: <SecurityIcon sx={{ fontSize: 40, color: 'warning.main' }} />,
    },
    {
      title: '設定項目',
      value: '8',
      icon: <SettingsIcon sx={{ fontSize: 40, color: 'info.main' }} />,
    },
  ];

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        ダッシュボード
      </Typography>

                  <Box sx={{ flexGrow: 1 }}>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)', md: 'repeat(4, 1fr)' }, gap: 3 }}>
          {stats.map((stat) => (
            <Card key={stat.title}>
              <CardContent>
                <Box display="flex" alignItems="center" justifyContent="space-between">
                  <Box>
                    <Typography color="textSecondary" gutterBottom>
                      {stat.title}
                    </Typography>
                    <Typography variant="h4">
                      {stat.value}
                    </Typography>
                  </Box>
                  {stat.icon}
                </Box>
              </CardContent>
            </Card>
          ))}
        </Box>

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 3, mt: 3 }}>
          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom>
              最近のアクティビティ
            </Typography>
            <Typography variant="body2" color="textSecondary">
              ファイル「document.pdf」がダウンロードされました
            </Typography>
            <Typography variant="body2" color="textSecondary">
              新しいファイル「report.xlsx」が追加されました
            </Typography>
            <Typography variant="body2" color="textSecondary">
              セキュリティ設定が更新されました
            </Typography>
          </Paper>

          <Paper sx={{ p: 3 }}>
            <Typography variant="h6" gutterBottom>
              システム情報
            </Typography>
            <Typography variant="body2" color="textSecondary">
              プラグインバージョン: 1.0.0
            </Typography>
            <Typography variant="body2" color="textSecondary">
              WordPressバージョン: 6.4.0
            </Typography>
            <Typography variant="body2" color="textSecondary">
              PHPバージョン: 8.1.0
            </Typography>
          </Paper>
        </Box>
      </Box>
    </Box>
  );
};

export default Dashboard;