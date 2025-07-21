import React from 'react';
import {
  Box,
  Container,
  Typography,
  Card,
  CardContent,
  Stepper,
  Step,
  StepLabel,
  StepContent,
  Paper,
  List,
  ListItem,
  ListItemIcon,
  ListItemText,
  Alert,
  AlertTitle,
  Divider,
} from '@mui/material';
import {
  Folder as FolderIcon,
  Upload as UploadIcon,
  Link as LinkIcon,
  CheckCircle as CheckCircleIcon,
  Settings as SettingsIcon,
  CloudUpload as CloudUploadIcon,
  ContentCopy as ContentCopyIcon,
  Security as SecurityIcon,
  Warning as WarningIcon,
  Info as InfoIcon,
} from '@mui/icons-material';

const QuickStart: React.FC = () => {
  const steps = [
    {
      label: 'ルートディレクトリ設定',
      description: 'WordPress管理画面から対象ディレクトリを設定します',
      icon: <FolderIcon sx={{ fontSize: 40, color: 'primary.main' }} />,
      details: [
        'WordPress管理画面にログイン',
        '「BF Secret File Downloader」メニューをクリックします。',
        '「設定」タブを選択します。',
        '「対象ディレクトリ」フィールドの「参照」ボタンをクリックします。',
        'ディレクトリブラウザで対象フォルダを選択します。',
        '「選択」ボタンをクリックして確定します。',
        '「変更を保存」ボタンをクリックします。',
      ],
      tips: [
        'ディレクトリブラウザで新しいフォルダを作成することも可能です。',
      ],
      warnings: [
        'システムディレクトリ（/etc、/var/log等）は選択できません。',
        'WordPressのインストールディレクトリは選択できません。',
      ],
    },
    {
      label: 'ファイルアップロード',
      description: '設定したディレクトリにファイルをアップロードします',
      icon: <UploadIcon sx={{ fontSize: 40, color: 'success.main' }} />,
      details: [
        'メニューから「BF Secret File Downloader」>「ファイルリスト」をクリック',
        'ファイルブラウザが表示されます。',
        '「ファイル選択」ボタンをクリックしてファイルを選択します。',
        'または、ドラッグ&ドロップエリアにファイルをドロップします。アップロード完了後、ファイル一覧に表示されます。',
        'ファイル名、サイズ、更新日時が確認できます。',
        'ディレクトリアイコンをクリックしてフォルダ内を閲覧可能です。',
      ],
      tips: [
        'セキュリティ上、リスクになるファイル（PHP、CGI等）のアップロードはできません。',
        '複数ファイルの一括アップロードが可能です。',
      ],
      warnings: [
        'ファイルサイズ制限を超えるファイルはアップロードできません。',
      ],
    },
    {
      label: 'URLコピー',
      description: 'ファイルのダウンロードURLを取得して共有します',
      icon: <LinkIcon sx={{ fontSize: 40, color: 'info.main' }} />,
      details: [
        'ファイル一覧で対象ファイルを選択します。',
        '「URLをコピー」リンクをクリックします。',
        'ダウンロードURLがクリップボードにコピーされます。',
        'URLをリンク設定にペーストします。',
        'パスワード保護が設定されている場合は認証が必要です。',
      ],
      tips: [
        'パスワード保護を設定することで、アクセス制御が可能です',
        'URLは管理者のみが生成できます',
      ],
      warnings: [
        'URLの有効期限は設定で管理されます。',
        'パスワード保護なしのURLは誰でもアクセス可能です。',
      ],
    },
  ];

  const features = [
    {
      title: 'ディレクトリ管理',
      description: 'WordPress管理外の非公開フォルダからファイルを安全に配信。ブラウザ機能で直感的なファイル管理が可能です。',
      icon: <FolderIcon sx={{ fontSize: 60, color: 'primary.main', mb: 2 }} />,
      details: [
        'ディレクトリブラウザによる直感的なフォルダ選択',
        'フォルダ階層での整理されたファイル管理',
        '新しいディレクトリの作成機能',
        'アクセス権限の確認と表示',
      ],
    },
    {
      title: 'ファイルアップロード',
      description: 'ドラッグ&ドロップでの簡単アップロード。ファイルサイズ制限とセキュリティチェックで安全な配信を実現します。',
      icon: <CloudUploadIcon sx={{ fontSize: 60, color: 'success.main', mb: 2 }} />,
      details: [
        'ドラッグ&ドロップでのファイルアップロード',
        '複数ファイルの一括アップロード',
        'ファイルサイズ制限（1-100MB）',
        '危険なファイル拡張子の自動拒否',
      ],
    },
    {
      title: 'URL共有',
      description: 'ワンクリックでダウンロードURLを生成・コピー。パスワード保護機能でアクセス制御も可能です。',
      icon: <ContentCopyIcon sx={{ fontSize: 60, color: 'info.main', mb: 2 }} />,
      details: [
        'ワンクリックでURLコピー',
        'BASIC認証によるパスワード保護',
        'ディレクトリ単位でのアクセス制御',
        'セキュアなダウンロードリンク生成',
      ],
    },
  ];

  const securityFeatures = [
    {
      title: 'アクセス制御',
      items: [
        'BASIC認証によるパスワード保護',
        'ディレクトリ単位でのアクセス制御',
        '危険なファイル拡張子の自動拒否',
        'システムディレクトリへのアクセスブロック',
      ],
    },
    {
      title: 'システム保護',
      items: [
        'ファイルサイズ制限（最大100MB）',
        'AJAX通信時のセキュリティ検証',
        '権限チェックによる不正アクセス防止',
        'WordPress管理画面からの完全分離',
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
          <Typography variant="h2" component="h1" gutterBottom fontWeight={600} sx={{ color: 'white' }}>
            クイックスタート
          </Typography>
          <Typography variant="h5" sx={{ color: 'white' }}>
            3つの簡単な手順でファイル配信を開始
          </Typography>
        </Container>
      </Box>

      {/* ステップガイドセクション */}
      <Container maxWidth="lg" sx={{ py: 8 }}>
        <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
          セットアップ手順
        </Typography>
        <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
          以下の3つの手順で、安全なファイル配信システムを構築できます
        </Typography>

        <Box sx={{ maxWidth: 900, mx: 'auto' }}>
          <Stepper orientation="vertical">
            {steps.map((step, index) => (
              <Step key={index} active={true}>
                <StepLabel
                  StepIconComponent={() => (
                    <Box sx={{ display: 'flex', alignItems: 'center', justifyContent: 'center' }}>
                      {step.icon}
                    </Box>
                  )}
                >
                  <Typography variant="h5" fontWeight={600}>
                    {step.label}
                  </Typography>
                </StepLabel>
                <StepContent>
                  <Typography variant="body1" color="text.secondary" paragraph>
                    {step.description}
                  </Typography>

                  <Paper sx={{ p: 3, mb: 2, bgcolor: 'grey.50' }}>
                    <Typography variant="h6" gutterBottom fontWeight={600}>
                      詳細手順
                    </Typography>
                    <List dense>
                      {step.details.map((detail, detailIndex) => (
                        <ListItem key={detailIndex} sx={{ py: 0.5 }}>
                          <ListItemIcon sx={{ minWidth: 32 }}>
                            <CheckCircleIcon color="primary" fontSize="small" />
                          </ListItemIcon>
                          <ListItemText primary={detail} />
                        </ListItem>
                      ))}
                    </List>
                  </Paper>

                  <Paper sx={{ p: 3, mb: 2, bgcolor: 'info.50' }}>
                    <Typography variant="h6" gutterBottom fontWeight={600} color="info.main">
                      重要なポイント
                    </Typography>
                    <List dense>
                      {step.tips.map((tip, tipIndex) => (
                        <ListItem key={tipIndex} sx={{ py: 0.5 }}>
                          <ListItemIcon sx={{ minWidth: 32 }}>
                            <InfoIcon color="info" fontSize="small" />
                          </ListItemIcon>
                          <ListItemText primary={tip} />
                        </ListItem>
                      ))}
                    </List>
                  </Paper>

                  {step.warnings && step.warnings.length > 0 && (
                    <Paper sx={{ p: 3, bgcolor: 'warning.50' }}>
                      <Typography variant="h6" gutterBottom fontWeight={600} color="warning.main">
                        注意事項
                      </Typography>
                      <List dense>
                        {step.warnings.map((warning, warningIndex) => (
                          <ListItem key={warningIndex} sx={{ py: 0.5 }}>
                            <ListItemIcon sx={{ minWidth: 32 }}>
                              <WarningIcon color="warning" fontSize="small" />
                            </ListItemIcon>
                            <ListItemText primary={warning} />
                          </ListItem>
                        ))}
                      </List>
                    </Paper>
                  )}
                </StepContent>
              </Step>
            ))}
          </Stepper>
        </Box>
      </Container>

      {/* 機能概要セクション */}
      <Box sx={{ bgcolor: 'grey.50', py: 8 }}>
        <Container maxWidth="lg">
          <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
            主要機能
          </Typography>
          <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
            セキュリティを最優先に設計された機能群
          </Typography>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(3, 1fr)' }, gap: 4 }}>
            {features.map((feature, index) => (
              <Card key={index} sx={{ height: '100%' }}>
                <CardContent sx={{ textAlign: 'center', p: 4 }}>
                  {feature.icon}
                  <Typography variant="h5" gutterBottom fontWeight={600}>
                    {feature.title}
                  </Typography>
                  <Typography variant="body2" color="text.secondary" paragraph>
                    {feature.description}
                  </Typography>
                  <List dense>
                    {feature.details.map((detail, detailIndex) => (
                      <ListItem key={detailIndex} sx={{ py: 0.5 }}>
                        <ListItemIcon sx={{ minWidth: 24 }}>
                          <CheckCircleIcon color="success" fontSize="small" />
                        </ListItemIcon>
                        <ListItemText primary={detail} />
                      </ListItem>
                    ))}
                  </List>
                </CardContent>
              </Card>
            ))}
          </Box>
        </Container>
      </Box>

      {/* セキュリティ情報セクション */}
      <Container maxWidth="lg" sx={{ py: 8 }}>
        <Typography variant="h3" component="h2" textAlign="center" gutterBottom>
          セキュリティ機能
        </Typography>
        <Typography variant="h6" textAlign="center" color="text.secondary" paragraph sx={{ mb: 6 }}>
          最新のセキュリティ技術を採用
        </Typography>

        <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 4 }}>
          {securityFeatures.map((section, index) => (
            <Card key={index}>
              <CardContent>
                <Typography variant="h6" gutterBottom fontWeight={600}>
                  {section.title}
                </Typography>
                <List dense>
                  {section.items.map((item, itemIndex) => (
                    <ListItem key={itemIndex}>
                      <ListItemIcon>
                        <SecurityIcon color="success" />
                      </ListItemIcon>
                      <ListItemText primary={item} />
                    </ListItem>
                  ))}
                </List>
              </CardContent>
            </Card>
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
            対応環境とシステム要件
          </Typography>

          <Box sx={{ display: 'grid', gridTemplateColumns: { xs: '1fr', md: 'repeat(2, 1fr)' }, gap: 4 }}>
            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom fontWeight={600}>
                  対応環境
                </Typography>
                <List dense>
                  <ListItem>
                    <ListItemIcon>
                      <CheckCircleIcon color="success" />
                    </ListItemIcon>
                    <ListItemText primary="WordPress 5.0以上" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <CheckCircleIcon color="success" />
                    </ListItemIcon>
                    <ListItemText primary="PHP 7.4以上" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <CheckCircleIcon color="success" />
                    </ListItemIcon>
                    <ListItemText primary="MySQL 5.6以上" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <CheckCircleIcon color="success" />
                    </ListItemIcon>
                    <ListItemText primary="HTTPS対応必須" />
                  </ListItem>
                </List>
              </CardContent>
            </Card>

            <Card>
              <CardContent>
                <Typography variant="h6" gutterBottom fontWeight={600}>
                  設定可能項目
                </Typography>
                <List dense>
                  <ListItem>
                    <ListItemIcon>
                      <SettingsIcon color="primary" />
                    </ListItemIcon>
                    <ListItemText primary="BASIC認証の有効/無効" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <SettingsIcon color="primary" />
                    </ListItemIcon>
                    <ListItemText primary="ファイルサイズ制限（1-100MB）" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <SettingsIcon color="primary" />
                    </ListItemIcon>
                    <ListItemText primary="ダウンロードログ機能" />
                  </ListItem>
                  <ListItem>
                    <ListItemIcon>
                      <SettingsIcon color="primary" />
                    </ListItemIcon>
                    <ListItemText primary="セキュリティレベル設定" />
                  </ListItem>
                </List>
              </CardContent>
            </Card>
          </Box>
        </Container>
      </Box>
    </Box>
  );
};

export default QuickStart;