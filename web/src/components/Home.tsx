import React from 'react';
import { Link } from 'react-router-dom';
import {
  Box,
  Container,
  Typography,
  Button,
  Card,
  CardContent,
  Grid,
  Paper,
} from '@mui/material';
import {
  Security as SecurityIcon,
  Download as DownloadIcon,
  Settings as SettingsIcon,
  Support as SupportIcon,
  CheckCircle as CheckCircleIcon,
} from '@mui/icons-material';

const Home: React.FC = () => {
  const features = [
    {
      icon: <DownloadIcon sx={{ fontSize: 40, color: 'primary.main' }} />,
      title: '非公開領域ファイル配信',
      description: 'WordPress管理外の非公開フォルダからファイルを安全にダウンロード',
    },
    {
      icon: <SecurityIcon sx={{ fontSize: 40, color: 'success.main' }} />,
      title: '簡易パスワード認証',
      description: 'シンプルなパスワードによるアクセス制御',
    },
    {
      icon: <SettingsIcon sx={{ fontSize: 40, color: 'info.main' }} />,
      title: '簡単設定',
      description: '直感的な操作で素早くセットアップ完了',
    },
    {
      icon: <SecurityIcon sx={{ fontSize: 40, color: 'warning.main' }} />,
      title: 'セキュリティ重視',
      description: 'IP制限やアクセスログなど、セキュリティを最優先に設計',
    },
  ];

  const benefits = [
    'BASIC認証による強固なセキュリティ',
    'IPアドレス制限機能',
    'ファイルタイプ制限',
    'ダウンロード履歴記録',
    'メール通知機能',
    '簡単なインストールと設定',
  ];

  return (
    <Box>
      {/* ヒーローセクション */}
      <Box
        sx={{
          bgcolor: 'primary.main',
          color: 'white',
          py: 8,
          textAlign: 'center',
        }}
      >
        <Container maxWidth="lg">
          <Typography variant="h2" component="h1" gutterBottom fontWeight={600}>
            BF Secret File Downloader
          </Typography>
          <Typography variant="h5" paragraph sx={{ mb: 4, opacity: 0.9 }}>
            WordPressの管理画面外にある機密ファイルを安全に管理・配信するセキュアなプラグイン
          </Typography>
          <Box sx={{ display: 'flex', gap: 2, justifyContent: 'center', flexWrap: 'wrap' }}>
            <Button
              component={Link}
              to="/docs"
              variant="contained"
              size="large"
              sx={{ bgcolor: 'white', color: 'primary.main', '&:hover': { bgcolor: 'grey.100' } }}
            >
              詳細を見る
            </Button>
            <Button
              component={Link}
              to="/features"
              variant="outlined"
              size="large"
              sx={{ color: 'white', borderColor: 'white', '&:hover': { borderColor: 'white', bgcolor: 'rgba(255,255,255,0.1)' } }}
            >
              機能一覧
            </Button>
          </Box>
        </Container>
      </Box>

      {/* 機能セクション */}
      <Container maxWidth="lg" sx={{ py: 8 }}>
        <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
          主要機能
        </Typography>
        <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
          セキュリティを最優先に設計された機能群
        </Typography>

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 4 }}>
          {features.map((feature, index) => (
            <Card key={index} sx={{ height: '100%' }}>
              <CardContent sx={{ textAlign: 'center', p: 4 }}>
                <Box sx={{ mb: 2 }}>
                  {feature.icon}
                </Box>
                <Typography variant="h5" component="h3" gutterBottom fontWeight={600}>
                  {feature.title}
                </Typography>
                <Typography variant="body1" color="text.secondary">
                  {feature.description}
                </Typography>
              </CardContent>
            </Card>
          ))}
        </Box>
      </Container>

      {/* メリットセクション */}
      <Box sx={{ bgcolor: 'grey.50', py: 8 }}>
        <Container maxWidth="lg">
          <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
            なぜBF Secret File Downloaderを選ぶのか
          </Typography>
          <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
            他のプラグインにはない独自のセキュリティ機能
          </Typography>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 3 }}>
            {benefits.map((benefit, index) => (
              <Box key={index} display="flex" alignItems="center" sx={{ mb: 2 }}>
                <CheckCircleIcon sx={{ color: 'success.main', mr: 2 }} />
                <Typography variant="body1">
                  {benefit}
                </Typography>
              </Box>
            ))}
          </Box>
        </Container>
      </Box>

      {/* CTAセクション */}
      <Container maxWidth="lg" sx={{ py: 8, textAlign: 'center' }}>
        <Paper sx={{ p: 6, bgcolor: 'primary.main', color: 'white' }}>
          <Typography variant="h4" component="h2" gutterBottom fontWeight={600}>
            今すぐ始めましょう
          </Typography>
          <Typography variant="h6" paragraph sx={{ mb: 4, opacity: 0.9 }}>
            無料でダウンロードして、セキュアなファイル管理を体験してください
          </Typography>
          <Button
            component={Link}
            to="/docs"
            variant="contained"
            size="large"
            sx={{ bgcolor: 'white', color: 'primary.main', '&:hover': { bgcolor: 'grey.100' } }}
          >
            無料ダウンロード
          </Button>
        </Paper>
      </Container>
    </Box>
  );
};

export default Home;