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
        'メニューから「BF Secret File Downloader」をクリックします。',
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

    </Box>
  );
};

export default QuickStart;