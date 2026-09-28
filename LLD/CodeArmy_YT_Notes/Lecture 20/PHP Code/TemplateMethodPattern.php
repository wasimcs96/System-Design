<?php

// ───────────────────────────────────────────────────────────
// 1. Base class defining the template method
// ───────────────────────────────────────────────────────────
abstract class ModelTrainer
{
    // The template method — final so subclasses can't change the sequence
    final public function trainPipeline(string $dataPath): void
    {
        $this->loadData($dataPath);
        $this->preprocessData();
        $this->trainModel();      // subclass-specific
        $this->evaluateModel();   // subclass-specific
        $this->saveModel();       // subclass-specific or default
    }

    protected function loadData(string $path): void
    {
        echo "[Common] Loading dataset from " . $path . PHP_EOL;
        // e.g., read CSV, images, etc.
    }

    protected function preprocessData(): void
    {
        echo "[Common] Splitting into train/test and normalizing" . PHP_EOL;
    }

    abstract protected function trainModel(): void;
    abstract protected function evaluateModel(): void;

    // Provide a default save, but subclasses can override if needed
    protected function saveModel(): void
    {
        echo "[Common] Saving model to disk as default format" . PHP_EOL;
    }
}

// ───────────────────────────────────────────────────────────
// 2. Concrete subclass: Neural Network
// ───────────────────────────────────────────────────────────
class NeuralNetworkTrainer extends ModelTrainer
{
    protected function trainModel(): void
    {
        echo "[NeuralNet] Training Neural Network for 100 epochs" . PHP_EOL;
        // pseudo-code: forward/backward passes, gradient descent...
    }

    protected function evaluateModel(): void
    {
        echo "[NeuralNet] Evaluating accuracy and loss on validation set" . PHP_EOL;
    }

    protected function saveModel(): void
    {
        echo "[NeuralNet] Serializing network weights to .h5 file" . PHP_EOL;
    }
}

// ───────────────────────────────────────────────────────────
// 3. Concrete subclass: Decision Tree
// ───────────────────────────────────────────────────────────
class DecisionTreeTrainer extends ModelTrainer
{
    // Use the default preprocessData() (train/test split + normalize)

    protected function trainModel(): void
    {
        echo "[DecisionTree] Building decision tree with max_depth=5" . PHP_EOL;
        // pseudo-code: recursive splitting on features...
    }

    protected function evaluateModel(): void
    {
        echo "[DecisionTree] Computing classification report (precision/recall)" . PHP_EOL;
    }
    // use the default saveModel()
}

// ───────────────────────────────────────────────────────────
// 4. Usage
// ───────────────────────────────────────────────────────────
class TemplateMethodPattern
{
    public static function main(): void
    {
        echo "=== Neural Network Training ===" . PHP_EOL;
        /** @var ModelTrainer $nnTrainer */
        $nnTrainer = new NeuralNetworkTrainer();
        $nnTrainer->trainPipeline("data/images/");

        echo PHP_EOL . "=== Decision Tree Training ===" . PHP_EOL;
        $dtTrainer = new DecisionTreeTrainer();
        $dtTrainer->trainPipeline("data/iris.csv");
    }
}

TemplateMethodPattern::main();
