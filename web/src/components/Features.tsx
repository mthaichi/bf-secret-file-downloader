import React from 'react';
import {
  Box,
  Container,
  Typography,
  Card,
  CardContent,
  Paper,
  Grid,
} from '@mui/material';
import {
  Security as SecurityIcon,
  Download as DownloadIcon,
  Settings as SettingsIcon,
} from '@mui/icons-material';

const Features: React.FC = () => {
  const features = [
    {
      title: '非公開領域ファイル配信',
      description: 'WordPressの管理画面外にある非公開フォルダからファイルを安全にダウンロードできる機能です。通常のWordPressでは管理できない、サーバーの非公開領域に配置されたファイルにアクセス制御をかけて配信することができます。管理者は対象ディレクトリを指定し、その中にあるファイルをブラウザで閲覧・管理できます。ファイルのアップロード、削除、フォルダ作成なども管理画面から直接実行可能です。',
      icon: <DownloadIcon sx={{ fontSize: 40, color: 'primary.main' }} />,
      details: [
        'WordPress管理外の非公開フォルダからファイル配信',
        'ファイルブラウザ機能で直感的なファイル管理',
        'ドラッグ&ドロップでのファイルアップロード',
        'フォルダ階層での整理されたファイル管理',
        'ファイルサイズ制限（1-100MB）による安全な配信',
      ],
    },
    {
      title: '簡易パスワード認証',
      description: 'ディレクトリ単位でパスワード保護を設定できる機能です。各フォルダに個別のパスワードを設定し、アクセス時にBASIC認証でパスワードを要求します。パスワードは暗号化して保存され、管理者のみが確認可能です。複数のフォルダに異なるパスワードを設定することで、アクセス権限を細かく制御できます。パスワードの設定・削除は管理画面から簡単に行えます。',
      icon: <SecurityIcon sx={{ fontSize: 40, color: 'success.main' }} />,
      details: [
        'ディレクトリ単位でのパスワード保護',
        'BASIC認証によるシンプルなアクセス制御',
        'パスワードの暗号化保存',
        '管理者のみがパスワード確認可能',
        '複数フォルダでの個別パスワード設定',
      ],
    },
    {
      title: '簡単設定画面',
      description: '直感的で使いやすい管理画面を提供します。対象ディレクトリの選択はブラウザ機能で簡単に行え、設定項目も最小限に絞られています。ファイル管理画面では、ファイルの一覧表示、ソート機能、ページネーション、検索機能など、効率的なファイル管理に必要な機能を備えています。設定変更時には自動的に既存のパスワード設定をクリアし、セキュリティを保ちながら設定を変更できます。',
      icon: <SettingsIcon sx={{ fontSize: 40, color: 'info.main' }} />,
      details: [
        'ブラウザ機能によるディレクトリ選択',
        'ファイル一覧のソート・ページネーション',
        'ドラッグ&ドロップでのファイル操作',
        '設定変更時の自動セキュリティ処理',
        '直感的なユーザーインターフェース',
      ],
    },
    {
      title: 'セキュリティ配慮',
      description: 'セキュリティを最優先に設計された機能群です。危険なファイル拡張子（PHP、CGI等）のアップロードを自動的に拒否し、ファイルサイズ制限によりサーバー負荷を軽減します。アクセス可能なディレクトリを制限し、システムディレクトリへのアクセスを完全にブロックします。また、AJAX通信時のnonce検証や権限チェックにより、不正アクセスを防ぎます。',
      icon: <SecurityIcon sx={{ fontSize: 40, color: 'warning.main' }} />,
      details: [
        '危険なファイル拡張子の自動拒否',
        'ファイルサイズ制限による負荷軽減',
        'システムディレクトリへのアクセスブロック',
        'AJAX通信時のセキュリティ検証',
        '権限チェックによる不正アクセス防止',
      ],
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
            機能一覧
          </Typography>
          <Typography variant="h5" paragraph>
            セキュリティを最優先に設計された機能群
          </Typography>
        </Container>
      </Box>

      {/* 機能詳細セクション */}
      <Container maxWidth="lg" sx={{ py: 8 }}>
        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 4 }}>
          {features.map((feature, index) => (
            <Paper key={index} sx={{ p: 4, height: '100%' }}>
              <Box display="flex" alignItems="flex-start" mb={3}>
                <Box sx={{ mr: 3, flexShrink: 0 }}>
                  {feature.icon}
                </Box>
                <Box>
                  <Typography variant="h4" component="h2" gutterBottom fontWeight={600}>
                    {feature.title}
                  </Typography>
                  <Typography variant="body1" color="text.secondary" paragraph>
                    {feature.description}
                  </Typography>
                </Box>
              </Box>

              {/* ダミー画像 */}
              <Box
                sx={{
                  width: '100%',
                  height: 200,
                  bgcolor: 'grey.200',
                  borderRadius: 1,
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  mb: 2
                }}
              >
                <Typography variant="body2" color="text.secondary">
                  {feature.title}のスクリーンショット
                </Typography>
              </Box>
            </Paper>
          ))}
        </Box>
      </Container>

      {/* 技術仕様セクション */}
      <Box sx={{ bgcolor: 'grey.50', py: 8 }}>
        <Container maxWidth="lg">
          <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
            技術仕様
          </Typography>
          <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
            最新のセキュリティ技術を採用
          </Typography>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 4 }}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom fontWeight={600}>
                  対応環境
                </Typography>
                <Typography variant="body2" paragraph>
                  • WordPress 5.0以上
                </Typography>
                <Typography variant="body2" paragraph>
                  • PHP 7.4以上
                </Typography>
                <Typography variant="body2" paragraph>
                  • MySQL 5.6以上
                </Typography>
                <Typography variant="body2" paragraph>
                  • HTTPS対応必須
                </Typography>
              </CardContent>
            </Card>

            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom fontWeight={600}>
                  セキュリティ機能
                </Typography>
                <Typography variant="body2" paragraph>
                  • BASIC認証（HTTP認証）
                </Typography>
                <Typography variant="body2" paragraph>
                  • ディレクトリパスワード保護
                </Typography>
                <Typography variant="body2" paragraph>
                  • ファイルタイプ制限
                </Typography>
                <Typography variant="body2" paragraph>
                  • アクセス制御
                </Typography>
              </CardContent>
            </Card>
          </Box>
        </Container>
      </Box>
    </Box>
  );
};

export default Features;