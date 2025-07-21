import React from 'react';
import {
  Box,
  Container,
  Typography,
  Paper,
  TextField,
  Button,
  Grid,
  Card,
  CardContent,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
} from '@mui/material';
import {
  Email as EmailIcon,
  GitHub as GitHubIcon,
  Support as SupportIcon,
  Security as SecurityIcon,
  CheckCircle as CheckCircleIcon,
} from '@mui/icons-material';

const Contact: React.FC = () => {
  const contactMethods = [
    {
      icon: <EmailIcon sx={{ fontSize: 40, color: 'primary.main' }} />,
      title: 'メールサポート',
      description: 'support@example.com',
      details: '24時間以内に返信いたします',
    },
    {
      icon: <GitHubIcon sx={{ fontSize: 40, color: 'text.primary' }} />,
      title: 'GitHub Issues',
      description: 'GitHub Issues',
      details: '技術的な問題やバグ報告',
    },
    {
      icon: <SupportIcon sx={{ fontSize: 40, color: 'success.main' }} />,
      title: '技術サポート',
      description: '専門チームによるサポート',
      details: 'インストールから設定まで',
    },
  ];

  const faqItems = [
    {
      question: 'BASIC認証の設定方法は？',
      answer: '管理画面の設定ページでユーザー名とパスワードを設定できます。',
    },
    {
      question: 'IPアドレス制限はどのように設定しますか？',
      answer: '許可したいIPアドレスを設定ページで指定してください。',
    },
    {
      question: '対応しているファイル形式は？',
      answer: 'PDF、Word、Excel、PowerPoint、画像ファイルなどに対応しています。',
    },
    {
      question: 'ダウンロード履歴はどこで確認できますか？',
      answer: '管理画面のダッシュボードでダウンロード履歴を確認できます。',
    },
  ];

  return (
    <Box>
      {/* ヘッダーセクション */}
      <Box
        sx={{
          bgcolor: 'primary.main',
          color: 'white',
          py: 6,
          textAlign: 'center',
        }}
      >
        <Container maxWidth="lg">
          <Typography variant="h2" component="h1" gutterBottom fontWeight={600}>
            お問い合わせ
          </Typography>
          <Typography variant="h5" paragraph>
            ご質問やサポートが必要な場合は、お気軽にお問い合わせください
          </Typography>
        </Container>
      </Box>

      <Container maxWidth="lg" sx={{ py: 8 }}>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 6 }}>
          {/* お問い合わせフォーム */}
          <Box>
            <Paper sx={{ p: 4 }}>
              <Typography variant="h4" component="h2" gutterBottom fontWeight={600}>
                お問い合わせフォーム
              </Typography>
              <Typography variant="body1" color="text.secondary" paragraph>
                以下のフォームにご記入の上、お送りください。
              </Typography>

              <Box component="form" sx={{ mt: 3 }}>
                <Box sx={{ display: 'grid', gap: 3 }}>
                  <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', sm: 'repeat(2, 1fr)' }, gap: 3 }}>
                    <TextField
                      fullWidth
                      label="お名前"
                      required
                      variant="outlined"
                    />
                    <TextField
                      fullWidth
                      label="メールアドレス"
                      required
                      type="email"
                      variant="outlined"
                    />
                  </Box>
                  <TextField
                    fullWidth
                    label="件名"
                    required
                    variant="outlined"
                  />
                  <TextField
                    fullWidth
                    label="お問い合わせ内容"
                    required
                    multiline
                    rows={6}
                    variant="outlined"
                  />
                  <Button
                    variant="contained"
                    size="large"
                    fullWidth
                    sx={{ py: 1.5 }}
                  >
                    送信する
                  </Button>
                </Box>
              </Box>
            </Paper>
          </Box>

          {/* 連絡方法 */}
          <Box>
            <Box sx={{ display: 'grid', gap: 3 }}>
              {contactMethods.map((method, index) => (
                <Card key={index}>
                  <CardContent>
                    <Box display="flex" alignItems="center" mb={2}>
                      <Box sx={{ mr: 2 }}>
                        {method.icon}
                      </Box>
                      <Box>
                        <Typography variant="h6" fontWeight={600}>
                          {method.title}
                        </Typography>
                        <Typography variant="body1" color="primary" fontWeight={600}>
                          {method.description}
                        </Typography>
                      </Box>
                    </Box>
                    <Typography variant="body2" color="text.secondary">
                      {method.details}
                    </Typography>
                  </CardContent>
                </Card>
              ))}
            </Box>
          </Box>
        </Box>

        {/* FAQセクション */}
        <Box sx={{ mt: 8 }}>
          <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
            よくある質問
          </Typography>
          <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
            よくいただく質問と回答をご紹介します
          </Typography>

          <Box sx={{ display: 'grid', gap: 3 }}>
            {faqItems.map((item, index) => (
              <Paper key={index} sx={{ p: 3 }}>
                <Typography variant="h6" gutterBottom fontWeight={600} color="primary">
                  Q. {item.question}
                </Typography>
                <Typography variant="body1" color="text.secondary">
                  A. {item.answer}
                </Typography>
              </Paper>
            ))}
          </Box>
        </Box>

        {/* サポート情報 */}
        <Box sx={{ mt: 8, bgcolor: 'grey.50', p: 4, borderRadius: 2 }}>
          <Typography variant="h4" component="h2" textAlign="center" gutterBottom>
            サポート情報
          </Typography>
          <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 4 }}>
            迅速で丁寧なサポートを提供いたします
          </Typography>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(3, 1fr)' }, gap: 4 }}>
            <Box textAlign="center">
              <SecurityIcon sx={{ fontSize: 60, color: 'primary.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom fontWeight={600}>
                セキュリティ専門
              </Typography>
              <Typography variant="body2" color="text.secondary">
                セキュリティに特化した専門チームがサポートいたします
              </Typography>
            </Box>
            <Box textAlign="center">
              <SupportIcon sx={{ fontSize: 60, color: 'success.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom fontWeight={600}>
                24時間対応
              </Typography>
              <Typography variant="body2" color="text.secondary">
                緊急時は24時間体制でサポートいたします
              </Typography>
            </Box>
            <Box textAlign="center">
              <CheckCircleIcon sx={{ fontSize: 60, color: 'info.main', mb: 2 }} />
              <Typography variant="h6" gutterBottom fontWeight={600}>
                無料サポート
              </Typography>
              <Typography variant="body2" color="text.secondary">
                基本的なサポートは無料でご提供いたします
              </Typography>
            </Box>
          </Box>
        </Box>
      </Container>
    </Box>
  );
};

export default Contact;